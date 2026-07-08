<?php

declare(strict_types=1);

/*
 * Automatic distance calculation for phase 2.
 *
 * The property address is geocoded through Nominatim/OpenStreetMap and route
 * durations are calculated through OSRM. Results are persisted in the existing
 * property_has_poles table so search sorting/filters keep using the same data.
 */
class MapDistanceService
{
    private const POLE_COORDINATES = [
        'polo_coppito' => ['lat' => 42.3690332, 'lon' => 13.3497548],
        'polo_roio' => ['lat' => 42.3396578, 'lon' => 13.3745224],
        'polo_centro' => ['lat' => 42.3553831, 'lon' => 13.3998201],
    ];

    public function ensureForProperty(array $property): array
    {
        $propertyId = (int) ($property['id'] ?? 0);
        if ($propertyId <= 0) {
            return ['ok' => false, 'message' => 'Annuncio non valido.'];
        }

        $geoRepo = new GeoRepository();
        $poles = $geoRepo->allPoles();
        if (!$this->needsSync($propertyId, $poles)) {
            return ['ok' => true, 'message' => 'Distanze gia aggiornate.', 'skipped' => true];
        }

        return $this->syncForProperty($property, $poles);
    }

    public function syncForProperty(array $property, ?array $poles = null, bool $clearOnFailure = false): array
    {
        $propertyId = (int) ($property['id'] ?? 0);
        if ($propertyId <= 0) {
            return ['ok' => false, 'message' => 'Annuncio non valido.'];
        }

        $poles ??= (new GeoRepository())->allPoles();
        $repo = new PropertyRepository();
        $origin = $this->geocodeProperty($property);

        if ($origin === null) {
            if ($clearOnFailure) {
                $repo->replacePoleDistances($propertyId, []);
            }
            return ['ok' => false, 'message' => 'Indirizzo non trovato sulla mappa: verifica via, civico e CAP.'];
        }

        $destinations = [];
        foreach ($poles as $pole) {
            $coords = $this->poleCoordinates((string) ($pole['code'] ?? ''));
            if ($coords !== null) {
                $destinations[] = [
                    'pole_id' => (int) $pole['id'],
                    'coords' => $coords,
                ];
            }
        }

        if ($destinations === []) {
            return ['ok' => false, 'message' => 'Coordinate dei poli universitari non configurate.'];
        }

        $durations = $this->routeDurations($origin, array_column($destinations, 'coords'));
        if ($durations === []) {
            if ($clearOnFailure) {
                $repo->replacePoleDistances($propertyId, []);
            }
            return ['ok' => false, 'message' => 'Distanze non calcolabili dalla mappa in questo momento.'];
        }

        $rows = [];
        foreach ($destinations as $index => $destination) {
            $seconds = $durations[$index] ?? null;
            if (!is_numeric($seconds) || (float) $seconds <= 0) {
                continue;
            }
            $rows[] = [
                'pole_id' => (int) $destination['pole_id'],
                'distance_minutes' => max(1, (int) ceil(((float) $seconds) / 60)),
                'transit_type' => 'car',
            ];
        }

        if ($rows === []) {
            return ['ok' => false, 'message' => 'Nessuna distanza valida restituita dalla mappa.'];
        }

        $repo->replacePoleDistances($propertyId, $rows);

        return [
            'ok' => true,
            'message' => 'Distanze calcolate automaticamente dalla mappa.',
            'count' => count($rows),
        ];
    }

    private function needsSync(int $propertyId, array $poles): bool
    {
        $rows = (new PropertyRepository())->polesFor($propertyId);
        if (count($rows) < count($poles)) {
            return true;
        }

        foreach ($rows as $row) {
            if ((string) ($row['transit_type'] ?? '') !== 'car' || (int) ($row['distance_minutes'] ?? 0) <= 0) {
                return true;
            }
        }

        return false;
    }

    private function geocodeProperty(array $property): ?array
    {
        $parts = [
            trim((string) ($property['address'] ?? '') . ' ' . (string) ($property['house_number'] ?? '')),
            (string) ($property['postal_code'] ?? '67100'),
            "L'Aquila",
            'Italia',
        ];
        $query = implode(', ', array_filter($parts, static fn (string $part): bool => trim($part) !== ''));
        $url = rtrim(MAP_GEOCODER_URL, '?') . '?format=jsonv2&limit=1&countrycodes=it&q=' . rawurlencode($query);
        $data = $this->httpJson($url);

        if (!is_array($data) || !isset($data[0]['lat'], $data[0]['lon'])) {
            return null;
        }

        return [
            'lat' => (float) $data[0]['lat'],
            'lon' => (float) $data[0]['lon'],
        ];
    }

    private function routeDurations(array $origin, array $destinations): array
    {
        $coordinates = [$origin];
        foreach ($destinations as $destination) {
            $coordinates[] = $destination;
        }

        $encoded = implode(';', array_map(
            static fn (array $point): string => $point['lon'] . ',' . $point['lat'],
            $coordinates
        ));
        $destinationIndexes = implode(';', range(1, count($coordinates) - 1));
        $url = rtrim(MAP_ROUTER_TABLE_URL, '/') . '/' . $encoded
            . '?sources=0&destinations=' . $destinationIndexes . '&annotations=duration';

        $data = $this->httpJson($url);
        if (!is_array($data) || ($data['code'] ?? '') !== 'Ok' || !isset($data['durations'][0]) || !is_array($data['durations'][0])) {
            return [];
        }

        return $data['durations'][0];
    }

    private function poleCoordinates(string $code): ?array
    {
        return self::POLE_COORDINATES[$code] ?? null;
    }

    private function httpJson(string $url): ?array
    {
        $body = null;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch !== false) {
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_TIMEOUT => 8,
                    CURLOPT_USERAGENT => MAP_HTTP_USER_AGENT,
                    CURLOPT_HTTPHEADER => ['Accept: application/json'],
                ]);
                $result = curl_exec($ch);
                $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                curl_close($ch);
                if (is_string($result) && $status >= 200 && $status < 300) {
                    $body = $result;
                }
            }
        }

        if ($body === null) {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 8,
                    'header' => "User-Agent: " . MAP_HTTP_USER_AGENT . "\r\nAccept: application/json\r\n",
                ],
            ]);
            $result = @file_get_contents($url, false, $context);
            if (is_string($result)) {
                $body = $result;
            }
        }

        if ($body === null || $body === '') {
            return null;
        }

        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : null;
    }
}
