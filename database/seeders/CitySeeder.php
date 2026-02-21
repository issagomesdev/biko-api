<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $response = Http::timeout(300)->get(
            'https://servicodados.ibge.gov.br/api/v1/localidades/municipios'
        );

        if ($response->failed()) {
            throw new \Exception('Erro ao buscar cidades do IBGE');
        }

        $cities = $response->json();

        foreach ($cities as $item) {

            $cityName = $item['nome'] ?? null;
            $uf = data_get($item, 'microrregiao.mesorregiao.UF.sigla');

            if (! $cityName || ! $uf) {
                continue;
            }

            $state = State::where('uf', $uf)->first();

            if ($state) {
                City::create([
                    'name' => $cityName,
                    'state_id' => $state->id,
                ]);
            }
        }
    }
}
