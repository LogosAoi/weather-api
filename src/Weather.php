<?php

namespace LogosAoi\Weather;



use Illuminate\Support\Facades\Config;
use LogosAoi\Weather\Exceptions\InvalidConfiguration;

class Weather
{
    
    protected $current = 'weather?';

   
    protected $one_call = 'onecall?';

   
    protected $forecast = 'forecast?';

  

    protected $historical = 'onecall/timemachine?';

   

    protected $air_pollution = 'air_pollution?';
    /**
     * temp_format available strings are c, f, k.
     *
     * @var string
     */


    protected $lang;

   
    protected $units = [
        'c' => 'metric',
        'f' => 'imperial',
        'k' => 'standard',
    ];

    protected $uom;

    protected $format;
 

    public function __construct()
    {
        self::setApi();
        self::setConfigParameters();
    }

    protected function setApi()
    {
        $this->api_key = config('openweather.api_key');
        if ($this->api_key == '') {
            throw InvalidConfiguration::apiKeyNotSpecified();
        }
    }

    protected function setConfigParameters()
    {
        $this->format = (object) config('openweather');
        $this->format->dt_format = $this->format->date_format.' '.$this->format->time_format;
        $this->uom = $this->units[$this->format->temp_format];
    }

   

    private function params(array $params)
    {
        $params['appid'] = $this->api_key;
        $params['units'] = $this->uom;
        $params['lang'] = $this->format->lang;

        return http_build_query($params);
    }



    private function getCurrent(array $query)
    {
        $route = $this->current.$this->params($query);
        $data = (new WeatherClient)->client()->fetch($route);

        return (new WeatherFormat($this->format))->formatCurrent($data);
    }

    
    private function getOneCall(array $query)
    {
        $route = $this->one_call.$this->params($query);
        $data = (new WeatherClient)->client()->fetch($route);

        return (new WeatherFormat($this->format))->formatOneCall($data);
    }

    

    private function get3Hourly(array $query)
    {
        $route = $this->forecast.$this->params($query);
        $data = (new WeatherClient)->client()->fetch($route);

        return (new WeatherFormat($this->format))->format3Hourly($data);
    }

    
    private function getHistorical(array $query)
    {
        $route = $this->historical.$this->params($query);
        $data = (new WeatherClient)->client()->fetch($route);

        return (new WeatherFormat($this->format))->formatHistorical($data);
    }


    /**
     * Geocoding API is a simple tool that we have developed to ease the search for locations while working with geographic names and coordinates.
     * documentation : https://openweathermap.org/api/geocoding-api.
     *
     * @param array $query
     *
     */

    private function getGeo(string $type, array $query)
    {
        $route = $type.$this->params($query);

        return (new WeatherClient)->client('geo')->fetch($route);
    }
    

    public function getCurrentByCity(string $city)
    {
        if (! is_numeric($city)) {
            $params['q'] = $city;
        } else {
            $params['id'] = $city;
        }

        return $this->getCurrent($params);
    }

    public function getCurrentByCord(string $lat, string $lon)
    {
        return $this->getCurrent([
            'lat' => $lat,
            'lon' => $lon,
        ]);
    }

    public function getCurrentByZip(string $zip, string $country = 'us')
    {
        return $this->getCurrent([
            'zip' => $zip,
            'country' => $country,
        ]);
    }

    public function getCurrentTempByCity(string $city)
    {
        if (! is_numeric($city)) {
            $params['q'] = $city;
        } else {
            $params['id'] = $city;
        }

        return $this->getCurrent($params)->main;
    }

    public function getOneCallByCord(string $lat, string $lon)
    {
        return $this->getOneCall([
            'lat' => $lat,
            'lon' => $lon,
        ]);
    }

    public function get3HourlyByCity(string $city)
    {
        if (! is_numeric($city)) {
            $params['q'] = $city;
        } else {
            $params['id'] = $city;
        }

        return $this->get3Hourly($params);
    }

    public function get3HourlyByZip(string $zip, string $country = 'us')
    {
        return $this->get3Hourly([
            'zip' => $zip,
            'country' => $country,
        ]);
    }

    public function get3HourlyByCord(string $lat, string $lon)
    {
        return $this->get3Hourly([
            'lat' => $lat,
            'lon' => $lon,
        ]);
    }

    public function getHistoryByCord(string $lat, string $lon, string $date)
    {
        return $this->getHistorical([
            'lat' => $lat,
            'lon' => $lon,
            'dt' => strtotime($date),
        ]);
    }


    public function getGeoByCity(string $city, string  $limit = null)
    {
        $params['q'] = $city;
        if ($limit) {
            $params['limit'] = $limit;
        }

        return $this->getGeo('direct?', $params);
    }

    public function getGeoByCord(string $lat, string $lon, string $limit = null)
    {
        $params = [
            'lat' => $lat,
            'lon' => $lon,
        ];
        if ($limit) {
            $params['limit'] = $limit;
        }

        return $this->getGeo('reverse?', $params);
    }
}
