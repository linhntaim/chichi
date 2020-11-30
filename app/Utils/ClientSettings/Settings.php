<?php

/**
 * Base - Any modification needs to be approved, except the space inside the block of TODO
 */


namespace App\Utils\ClientSettings;

use App\Utils\ConfigHelper;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Str;

class Settings implements ISettings, Arrayable, Jsonable
{
    protected $appId;
    protected $appName;
    protected $appUrl;

    protected $locale;
    protected $country;
    protected $timezone;
    protected $currency;
    protected $numberFormat;
    protected $firstDayOfWeek;
    protected $longDateFormat;
    protected $shortDateFormat;
    protected $longTimeFormat;
    protected $shortTimeFormat;

    protected $changes;

    public function __construct()
    {
        $this->appId = ConfigHelper::get('app.id');
        $this->appName = config('app.name');
        $this->appUrl = config('app.url');

        $this->locale = config('app.locale');
        $this->country = ConfigHelper::get('localization.country');
        $this->timezone = config('app.timezone');
        $this->currency = ConfigHelper::get('localization.currency');
        $this->numberFormat = ConfigHelper::get('localization.number_format');
        $this->firstDayOfWeek = ConfigHelper::get('localization.first_day_of_week');
        $this->longDateFormat = ConfigHelper::get('localization.long_date_format');
        $this->shortDateFormat = ConfigHelper::get('localization.short_date_format');
        $this->longTimeFormat = ConfigHelper::get('localization.long_time_format');
        $this->shortTimeFormat = ConfigHelper::get('localization.short_time_format');

        $this->clearChanges();
    }

    public function setAppId($appId)
    {
        $this->appId = $appId;
        return $this;
    }

    public function getAppId()
    {
        return $this->appId;
    }

    public function setAppName($appName)
    {
        $this->appName = $appName;
        return $this;
    }

    public function getAppName()
    {
        return $this->appName;
    }

    public function setAppUrl($appUrl)
    {
        $this->appUrl = $appUrl;
        return $this;
    }

    public function getAppUrl()
    {
        return $this->appUrl;
    }

    public function setLocale($locale)
    {
        if (!is_null($locale) && in_array($locale, ConfigHelper::getLocaleCodes())) {
            $this->locale = $locale;
        }
        return $this;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setCountry($country)
    {
        if (!is_null($country) && in_array($country, ConfigHelper::getCountryCodes())) {
            $this->country = $country;
        }
        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setTimezone($timezone)
    {
        if (!is_null($timezone) && in_array($timezone, DateTimer::getTimezoneValues())) {
            $this->timezone = $timezone;
        }
        return $this;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }

    public function setCurrency($currency)
    {
        if (!is_null($currency) && in_array($currency, ConfigHelper::getCurrencyCodes())) {
            $this->currency = $currency;
        }
        return $this;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setNumberFormat($numberFormat)
    {
        if (!is_null($numberFormat) && in_array($numberFormat, ConfigHelper::getNumberFormats())) {
            $this->numberFormat = $numberFormat;
        }
        return $this;
    }

    public function getNumberFormat()
    {
        return $this->numberFormat;
    }

    public function setFirstDayOfWeek($firstDayOfWeek)
    {
        if (!is_null($firstDayOfWeek) && in_array($firstDayOfWeek, DateTimer::getDaysOfWeekValues())) {
            $this->firstDayOfWeek = $firstDayOfWeek;
        }
        return $this;
    }

    public function getFirstDayOfWeek()
    {
        return $this->firstDayOfWeek;
    }

    public function setLongDateFormat($longDateFormat)
    {
        if (!is_null($longDateFormat) && in_array($longDateFormat, DateTimer::getLongDateFormatValues())) {
            $this->longDateFormat = $longDateFormat;
        }
        return $this;
    }

    public function getLongDateFormat()
    {
        return $this->longDateFormat;
    }

    public function setShortDateFormat($shortDateFormat)
    {
        if (!is_null($shortDateFormat) && in_array($shortDateFormat, DateTimer::getShortDateFormatValues())) {
            $this->shortDateFormat = $shortDateFormat;
        }
        return $this;
    }

    public function getShortDateFormat()
    {
        return $this->shortDateFormat;
    }

    public function setLongTimeFormat($longTimeFormat)
    {
        if (!is_null($longTimeFormat) && in_array($longTimeFormat, DateTimer::getLongTimeFormatValues())) {
            $this->longTimeFormat = $longTimeFormat;
        }
        return $this;
    }

    public function getLongTimeFormat()
    {
        return $this->longTimeFormat;
    }

    public function setShortTimeFormat($shortTimeFormat)
    {
        if (!is_null($shortTimeFormat) && DateTimer::getShortTimeFormatValues()) {
            $this->shortTimeFormat = $shortTimeFormat;
        }
        return $this;
    }

    public function getShortTimeFormat()
    {
        return $this->shortTimeFormat;
    }

    public function merge($settings)
    {
        if (is_array($settings)) {
            return $this->mergeFromArray($settings);
        } elseif ($settings instanceof Settings) {
            return $this->mergeFromOtherSettings($settings);
        }
        return $this;
    }

    public function mergeFromOtherSettings(Settings $settings)
    {
        foreach (array_keys(get_class_vars(static::class)) as $propertyName) {
            if (!is_null($settings->{$propertyName})) {
                $this->{$propertyName} = $settings->{$propertyName};
            }
        }
        return $this;
    }

    public function mergeFromArray(array $settings)
    {
        foreach ($settings as $key => $value) {
            $setMethod = sprintf('set%s', Str::studly($key));
            if (method_exists($this, $setMethod)) {
                $this->{$setMethod}($value);
            }
        }
        return $this;
    }

    public function clearChanges()
    {
        $this->changes = [];
    }

    public function toArray()
    {
        $data = [];
        foreach (array_keys(get_class_vars(static::class)) as $propertyName) {
            if ($propertyName == 'changes') continue;
            $data[Str::snake($propertyName)] = $this->{$propertyName};
        }
        return $data;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
}
