<?php
namespace arajcany\Tempus;

use Cake\Chronos\MutableDateTime;
use DateTime;


/**
 * Extension to the Cake\Chronos\Chronos class
 *
 * Added extra methods to roll to the start/end of periods
 * Ability to convert a string into a DateTime range
 */
class Tempus extends MutableDateTime
{

    /**
     * {@inheritDoc}
     */
    public function __construct($time = null, $tz = null)
    {
        parent::__construct($time, $tz);
    }

    /**
     * Resets the time to XX:00:00
     *
     * @return static
     */
    public function startOfHour()
    {
        $currentHour = $this->hour;
        return $this->modify("{$currentHour}:00:00");
    }

    /**
     * Resets the time to XX:59:59
     *
     * @return static
     */
    public function endOfHour()
    {
        $currentHour = $this->hour;
        return $this->modify("{$currentHour}:59:59");
    }

    /**
     * Resets the time to XX:XX:00
     *
     * @return static
     */
    public function startOfMinute()
    {
        $currentHour = $this->hour;
        $currentMin = $this->minute;
        return $this->modify("{$currentHour}:{$currentMin}:00");
    }

    /**
     * Resets the time to XX:XX:59
     *
     * @return static
     */
    public function endOfMinute()
    {
        $currentHour = $this->hour;
        $currentMin = $this->minute;
        return $this->modify("{$currentHour}:{$currentMin}:59");
    }

    /**
     * Resets the time to XX:XX:XX (in effect does nothing)
     *
     * @return static
     */
    public function startOfSecond()
    {
        $currentHour = $this->hour;
        $currentMin = $this->minute;
        $currentSec = $this->second;
        return $this->modify("{$currentHour}:{$currentMin}:{$currentSec}");
    }

    /**
     * Resets the time to XX:XX:XX (in effect does nothing)
     *
     * @return static
     */
    public function endOfSecond()
    {
        $currentHour = $this->hour;
        $currentMin = $this->minute;
        $currentSec = $this->second;
        return $this->modify("{$currentHour}:{$currentMin}:{$currentSec}");
    }

    /**
     * Resets the day the first day of the quarter
     *
     * @return static
     */
    public function startOfQuarter()
    {
        return $this->day(1)->month($this->quarter * 3 - 2)->startOfMonth();
    }

    /**
     * Resets the day the last day of the quarter
     *
     * @return static
     */
    public function endOfQuarter()
    {
        return $this->day(1)->month($this->quarter * 3)->endOfMonth();
    }

    /**
     * Shift the quarter by the given value
     *
     * @return static
     */
    public function addQuarters($value)
    {
        return $this->addMonths(3 * $value);
    }

    /**
     * Shift the quarter by the given value
     *
     * @return static
     */
    public function subQuarters($value)
    {
        return $this->subMonths(3 * $value);
    }


    /**
     * Convert a String into a TimeRange
     *
     * Example strings
     *  'now'
     *  'today'
     *  'tomorrow'
     *  'yesterday'
     *  'last month'
     *  'next year'
     *  'this quarter'
     *  '2016'
     *  'last 365 days'
     *  'next 3 weeks'
     *
     * You can also use combinations of the above examples
     *  'last week to next week'
     *  'last month to now'
     *  'last 300 days to last 10 days'
     *
     * @param string $inputString
     * @param int $firstDayOfWeek
     * @return array|bool
     */
    public function stringToTimeRange($inputString = '', $firstDayOfWeek = 0)
    {

        /*======== START $firstDayOfWeek Cleanup ======== */
        if ($firstDayOfWeek == 0) {
            Tempus::setWeekStartsAt(Tempus::SUNDAY);
            Tempus::setWeekEndsAt(Tempus::SATURDAY);
        } elseif ($firstDayOfWeek == 1) {
            Tempus::setWeekStartsAt(Tempus::MONDAY);
            Tempus::setWeekEndsAt(Tempus::SUNDAY);
        } elseif ($firstDayOfWeek == 2) {
            Tempus::setWeekStartsAt(Tempus::TUESDAY);
            Tempus::setWeekEndsAt(Tempus::MONDAY);
        } elseif ($firstDayOfWeek == 3) {
            Tempus::setWeekStartsAt(Tempus::WEDNESDAY);
            Tempus::setWeekEndsAt(Tempus::TUESDAY);
        } elseif ($firstDayOfWeek == 4) {
            Tempus::setWeekStartsAt(Tempus::THURSDAY);
            Tempus::setWeekEndsAt(Tempus::WEDNESDAY);
        } elseif ($firstDayOfWeek == 5) {
            Tempus::setWeekStartsAt(Tempus::FRIDAY);
            Tempus::setWeekEndsAt(Tempus::THURSDAY);
        } elseif ($firstDayOfWeek == 6) {
            Tempus::setWeekStartsAt(Tempus::SATURDAY);
            Tempus::setWeekEndsAt(Tempus::FRIDAY);
        } else {
            Tempus::setWeekStartsAt(Tempus::SUNDAY);
            Tempus::setWeekEndsAt(Tempus::SATURDAY);
        }
        /*======== END $firstDayOfWeek Cleanup ======== */


        $expressions = $this->stringToExpressions($inputString);
        //debug($expressions);
        $timeRange = $this->expressionsToTimeRange($expressions);
        //debug($timeRange);

        return $timeRange;
    }

    /**
     * Converts the Expression to TimeRange
     *
     * @param string $expressions
     * @return array|bool
     */
    public function expressionsToTimeRange($expressions = '')
    {
        if (isset($expressions['start']) && isset($expressions['end'])) {
            $returnTimeRange = [];

            //==== Start of Range ====================
            $startOfRange = Tempus::createFromFormat("Y-m-d H:i:s", $expressions['start']['baseTimestamp'],
                $this->timezoneName);
            //Roll to start of the unit
            $startOfUnit = 'startOf' . ucwords($expressions['start']['unit']);
            $startOfRange->$startOfUnit();
            if ($expressions['start']['direction'] == '-') {
                $mathOperation = 'sub' . ucwords($expressions['start']['unit']) . 's';
            } else {
                $mathOperation = 'add' . ucwords($expressions['start']['unit']) . 's';
            }
            $startOfRange->$mathOperation($expressions['start']['offset']);
            $returnTimeRange['start'] = $startOfRange;

            //==== End of Range ====================
            $endOfRange = Tempus::createFromFormat("Y-m-d H:i:s", $expressions['end']['baseTimestamp'],
                $this->timezoneName);
            //Roll to end of the unit
            $endOfUnit = 'endOf' . ucwords($expressions['end']['unit']);
            $endOfRange->$endOfUnit();
            if ($expressions['end']['direction'] == '-') {
                $mathOperation = 'sub' . ucwords($expressions['end']['unit']) . 's';
            } else {
                $mathOperation = 'add' . ucwords($expressions['end']['unit']) . 's';
            }
            $endOfRange->$mathOperation($expressions['end']['offset']);
            $returnTimeRange['end'] = $endOfRange;

            return $returnTimeRange;

        } else {
            return false;
        }
    }

    /**
     * Converts the String to Expressions
     *
     * @param string $inputString
     * @return array|bool
     */
    public function stringToExpressions($inputString = '')
    {
        //cleanup and explode
        $inputString = $this->cleanupString($inputString);
        $inputStringTmp = explode(' to ', $inputString);

        //create the start and end strings
        if (count($inputStringTmp) == 1) {
            //single date string, need to expand to start and end string
            if ($properties = $this->isStaticExpression($inputStringTmp[0], true)) {
                $startString = $properties;
                $endString = $properties;
            } elseif ($properties = $this->isDynamicExpression($inputStringTmp[0], true)) {
                if (strpos('next', $inputString[0]) !== false) {
                    $startString = $this->isStaticExpression('now', true);
                    $endString = $properties;
                } elseif (strpos('last', $inputString[0]) !== false) {
                    $startString = $properties;
                    $endString = $this->isStaticExpression('now', true);
                } else {
                    $startString = $properties;
                    $endString = $properties;
                }
            } elseif ($properties = $this->isFormatExpression($inputStringTmp[0], true)) {
                $startString = $properties;
                $endString = $properties;
            } elseif ($properties = $this->isTimestampExpression($inputStringTmp[0], true)) {
                $startString = $properties;
                $endString = $properties;
            } else {
                $startString = false;
                $endString = false;
            }
        } elseif (count($inputStringTmp) == 2) {
            //start and end string given

            //start string
            if ($properties = $this->isStaticExpression($inputStringTmp[0], true)) {
                $startString = $properties;
            } elseif ($properties = $this->isDynamicExpression($inputStringTmp[0], true)) {
                $startString = $properties;
            } elseif ($properties = $this->isFormatExpression($inputStringTmp[0], true)) {
                $startString = $properties;
            } elseif ($properties = $this->isTimestampExpression($inputStringTmp[0], true)) {
                $startString = $properties;
            } else {
                $startString = false;
            }

            //end string
            if ($properties = $this->isStaticExpression($inputStringTmp[1], true)) {
                $endString = $properties;
            } elseif ($properties = $this->isDynamicExpression($inputStringTmp[1], true)) {
                $endString = $properties;
            } elseif ($properties = $this->isFormatExpression($inputStringTmp[1], true)) {
                $endString = $properties;
            } elseif ($properties = $this->isTimestampExpression($inputStringTmp[1], true)) {
                $endString = $properties;
            } else {
                $endString = false;
            }
        } else {
            //more or less parts than expected
            $startString = false;
            $endString = false;
        }

        //return the expressions
        if ($startString && $endString) {
            $expressions = ['start' => $startString, 'end' => $endString];
            return $expressions;
        } else {
            return false;
        }
    }

    /**
     * Standardises the String into known keywords for the conversion process
     *
     * @param string $inputString
     * @return mixed|string
     */
    public function cleanupString($inputString = '')
    {
        //lower case
        $outputString = strtolower($inputString);

        //word substitution
        $substitutionTables = [
            'last' => ['previous', 'past'],
            'this' => ['current', 'present'],
            'next' => ['forward', 'future'],
            'second' => ['seconds', 'secs'],
            'minute' => ['minutes', 'mins'],
            'hour' => ['hours'],
            'day' => ['days'],
            'month' => ['months'],
            'year' => ['years'],
            'week' => ['weeks'],
            'quarter' => ['quarters'],
        ];
        foreach ($substitutionTables as $cleanWord => $dirtyWords) {
            foreach ($dirtyWords as $dirtyWord) {
                $outputString = str_replace($dirtyWord, $cleanWord, $outputString);
            }
        }

        return $outputString;
    }

    /**
     * Checks if the passed inputString is a Unix Timestamp integer
     *
     * @param string $inputString
     * @param bool|false $properties
     * @return array|bool
     */
    public function isTimestampExpression($inputString = '', $properties = false)
    {
        if (sha1($inputString) == sha1(intval($inputString))) {
            if ($properties == true) {
                return [
                    'expression' => $inputString,
                    'expressionType' => 'timestamp',
                    'unit' => 'second',
                    'offset' => 0,
                    'direction' => '+',
                    //because an explicit time() has been passed via the inputString, use it for the baseTimestamp
                    'baseTimestamp' => date('Y-m-d H:i:s', $inputString)
                ];
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Checks if the passed inputString is a Formatted Timestamp
     * e.g. Y-m-d H:i:s
     *
     * @param string $inputString
     * @param bool|false $properties
     * @return array|bool
     */
    public function isFormatExpression($inputString = '', $properties = false)
    {
        //tSql does not allow dates less than 1753, the adoption
        //of the Gregorian calendar for Britain and its colonies
        //anything less will revert to Unix epoch
        if (sha1($inputString) == sha1(intval($inputString))) {
            if ($inputString < 1753) {
                return false;
            }
        }

        $formatExpressions = $this->getFormatStrings();

        foreach ($formatExpressions as $formatExpression => $formatExpressionProperties) {
            $dt = DateTime::createFromFormat($formatExpression, $inputString);
            if ($dt !== false) {
                if ($properties == true) {
                    return array_merge(
                        ['expression' => $inputString],
                        ['expressionType' => 'format'],
                        $formatExpressionProperties,
                        //because an explicit time() has been passed via the inputString, use it for the baseTimestamp
                        ['baseTimestamp' => $dt->format('Y-m-d H:i:s')]
                    );
                } else {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks if the passed inputString is a valid Dynamic statement
     * e.g. [modifier] [int] [unit]
     *      [last] [3] [years]
     *      [next] [4] [days]
     *
     * @param string $inputString
     * @param bool|false $properties
     * @return array|bool
     */
    public function isDynamicExpression($inputString = '', $properties = false)
    {
        $inputString = $this->cleanupString($inputString);

        $relativeModifiers = [
            'last' => ['direction' => '-'],
            'next' => ['direction' => '+']
        ];
        $timeUnits = ['year', 'quarter', 'month', 'week', 'day', 'hour', 'minute', 'second'];

        $singleExpressionParts = explode(" ", $inputString);
        if (count($singleExpressionParts) == 3) {
            $relativeModifier = $singleExpressionParts[0];
            $timeOffset = intval($singleExpressionParts[1]);
            $timeUnit = $singleExpressionParts[2];

            //check the $relativeModifier
            if (in_array($relativeModifier, array_keys($relativeModifiers))) {
                $relativeDirection = $relativeModifiers[$relativeModifier]['direction'];
                $relativeModifierOk = true;
            } else {
                $relativeModifierOk = false;
            }

            //check the $timeOffset
            if ($timeOffset !== 0 && is_int($timeOffset)) {
                $timeOffsetOk = true;
            } else {
                $timeOffsetOk = false;
            }

            //check the $timeUnitOk
            if (in_array($timeUnit, $timeUnits)) {
                $timeUnitOk = true;
            } else {
                $timeUnitOk = false;
            }

            $returnFlag = true;
        } else {
            $returnFlag = false;
        }


        if ($returnFlag === true && $relativeModifierOk === true && $timeOffsetOk === true && $timeUnitOk === true) {
            if ($properties == true) {
                return [
                    'expression' => $inputString,
                    'expressionType' => 'dynamic',
                    'unit' => $timeUnit,
                    'offset' => $timeOffset,
                    'direction' => $relativeDirection,
                    //because inputString is relative, use the time() as passed to the Object Instantiation
                    'baseTimestamp' => $this->format('Y-m-d H:i:s')
                ];
            } else {
                return true;
            }
        } else {
            return false;
        }

    }

    /**
     * Checks if the passed inputString is a valid Static statement
     *
     * @param string $inputString
     * @param bool|false $properties
     * @return array|bool
     */
    public function isStaticExpression($inputString = '', $properties = false)
    {
        $inputString = $this->cleanupString($inputString);

        $staticExpressions = $this->getStaticStatements();

        if (isset($staticExpressions[$inputString])) {
            if ($properties == true) {
                return array_merge(
                    ['expression' => $inputString],
                    ['expressionType' => 'static'],
                    $staticExpressions[$inputString],
                    //because inputString is relative, use the time() as passed to the Object Instantiation
                    ['baseTimestamp' => $this->format('Y-m-d H:i:s')]
                );
            } else {
                return true;
            }
        } else {
            return false;
        }

    }

    /**
     * Returns a list of accepted date() format strings
     *
     * @return array
     */
    private function getFormatStrings()
    {
        $formatStrings = [
            "Y" => ['unit' => 'year', 'offset' => 0, 'direction' => '+'],
            "Y-m-d" => ['unit' => 'day', 'offset' => 0, 'direction' => '+'],
            "Y-m-d H:i:s" => ['unit' => 'second', 'offset' => 0, 'direction' => '+'],
            "H:i:s" => ['unit' => 'second', 'offset' => 0, 'direction' => '+'],
        ];

        return $formatStrings;
    }

    /**
     * Returns a list of accepted relative statements
     *
     * @return array
     */
    public function getStaticStatements()
    {
        $staticStatements = [
            //years
            'last year' => ['unit' => 'year', 'offset' => 1, 'direction' => '-'],
            'this year' => ['unit' => 'year', 'offset' => 0, 'direction' => '+'],
            'next year' => ['unit' => 'year', 'offset' => 1, 'direction' => '+'],
            //quarter
            'last quarter' => ['unit' => 'quarter', 'offset' => 1, 'direction' => '-'],
            'this quarter' => ['unit' => 'quarter', 'offset' => 0, 'direction' => '+'],
            'next quarter' => ['unit' => 'quarter', 'offset' => 1, 'direction' => '+'],
            //months
            'last month' => ['unit' => 'month', 'offset' => 1, 'direction' => '-'],
            'this month' => ['unit' => 'month', 'offset' => 0, 'direction' => '+'],
            'next month' => ['unit' => 'month', 'offset' => 1, 'direction' => '+'],
            //weeks
            'last week' => ['unit' => 'week', 'offset' => 1, 'direction' => '-'],
            'this week' => ['unit' => 'week', 'offset' => 0, 'direction' => '+'],
            'next week' => ['unit' => 'week', 'offset' => 1, 'direction' => '+'],
            //days
            'day before yesterday' => ['unit' => 'day', 'offset' => 2, 'direction' => '-'],
            'yesterday' => ['unit' => 'day', 'offset' => 1, 'direction' => '-'],
            'today' => ['unit' => 'day', 'offset' => 0, 'direction' => '+'],
            'tomorrow' => ['unit' => 'day', 'offset' => 1, 'direction' => '+'],
            'day after tomorrow' => ['unit' => 'day', 'offset' => 2, 'direction' => '+'],
            //hours
            'last hour' => ['unit' => 'hour', 'offset' => 1, 'direction' => '-'],
            'this hour' => ['unit' => 'hour', 'offset' => 0, 'direction' => '+'],
            'next hour' => ['unit' => 'hour', 'offset' => 1, 'direction' => '+'],
            //minutes
            'last minute' => ['unit' => 'minute', 'offset' => 1, 'direction' => '-'],
            'this minute' => ['unit' => 'minute', 'offset' => 0, 'direction' => '+'],
            'next minute' => ['unit' => 'minute', 'offset' => 1, 'direction' => '+'],
            //now/seconds
            'now' => ['unit' => 'second', 'offset' => 0, 'direction' => '+'],
        ];


        //generate dashed and underscored version
        $staticStatementsDashedAndUnderscored = [];
        foreach ($staticStatements as $key => $staticStatement) {
            $keyDashed = str_replace(' ', '-', $key);
            $keyUnderscored = str_replace(' ', '_', $key);
            $staticStatementsDashedAndUnderscored[$keyDashed] = $staticStatement;
            $staticStatementsDashedAndUnderscored[$keyUnderscored] = $staticStatement;
        }

        $staticStatements = array_merge($staticStatements, $staticStatementsDashedAndUnderscored);
        return $staticStatements;
    }

    public static function getTimezoneList($continents = null, $format = 'array')
    {
        $continentsDefault = [
            'Africa',
            'America',
            'Antarctica',
            'Arctic',
            'Asia',
            'Atlantic',
            'Australia',
            'Europe',
            'Indian',
            'Pacific',
        ];

        if (empty($continents) || !is_array($continents)) {
            $continents = $continentsDefault;
        }

        //return array
        $timezoneList = [];

        $zones = timezone_identifiers_list();
        foreach ($zones as $zone) {
            $zone = explode('/', $zone); // 0 => Continent, 1 => City
            // Only use "friendly" continent names
            if (in_array($zone[0], $continents)) {
                if (isset($zone[1]) != '') {
                    $timezoneList[$zone[0]][$zone[0] . '/' . $zone[1]] = str_replace('_', ' ',
                        $zone[1]); // Creates array(DateTimeZone => 'Friendly name')
                }
            }
        }

        if ($format == 'array') {
            return $timezoneList;
        } elseif ($format == 'json') {
            return json_encode($timezoneList, JSON_PRETTY_PRINT);
        }
    }


    /**
     * Try and guess the format of a date from a passed in string
     * Date string examples 2017-04-03 or 03/04/2017 or 04/03/2017
     * You can bias the guessing towards US or AU style dates
     *
     *
     * @param string $dateString
     * @param string $bias DMY || MDY  (AU || US style of dates)
     * @return bool|string
     */
    public static function guessFormatFromDateString($dateString = '', $bias = 'DMY')
    {
        $bias = strtoupper($bias);

        if ($bias != 'DMY' && $bias != 'MDY') {
            $bias = 'DMY';
        }

        if ($bias == 'DMY') {

            $result = self::ymdTest($dateString);
            if ($result) {
                return $result;
            }

            $result = self::dmyTest($dateString);
            if ($result) {
                return $result;
            }

            $result = self::mdyTest($dateString);
            if ($result) {
                return $result;
            }

            return false;
        }

        if ($bias == 'MDY') {

            $result = self::ymdTest($dateString);
            if ($result) {
                return $result;
            }

            $result = self::mdyTest($dateString);
            if ($result) {
                return $result;
            }

            $result = self::dmyTest($dateString);
            if ($result) {
                return $result;
            }

            return false;

        }

        return false;

    }

    private static function ymdTest($dateString = '')
    {
        $format = 'Y-m-d';
        $delimiter = '|';
        $symbols = [' ', '-', '_', '/', '.', ',', '\\'];

        $dateString = str_replace($symbols, $delimiter, $dateString);
        $dateArray = explode($delimiter, $dateString);
        if (count($dateArray) !== 3) {
            return false;
        }
        $year = $dateArray[0];
        $month = $dateArray[1];
        $day = $dateArray[2];

        $result = checkdate($month, $day, $year);
        if ($result) {
            return $format;
        } else {
            return false;
        }
    }

    private static function dmyTest($dateString = '')
    {
        $format = 'd/m/Y';
        $delimiter = '|';
        $symbols = [' ', '-', '_', '/', '.', ',', '\\'];

        $dateString = str_replace($symbols, $delimiter, $dateString);
        $dateArray = explode($delimiter, $dateString);
        if (count($dateArray) !== 3) {
            return false;
        }
        $year = $dateArray[2];
        $month = $dateArray[1];
        $day = $dateArray[0];

        $result = checkdate($month, $day, $year);
        if ($result) {
            return $format;
        } else {
            return false;
        }
    }

    private static function mdyTest($dateString = '')
    {
        $format = 'm/d/Y';
        $delimiter = '|';
        $symbols = [' ', '-', '_', '/', '.', ',', '\\'];

        $dateString = str_replace($symbols, $delimiter, $dateString);
        $dateArray = explode($delimiter, $dateString);
        if (count($dateArray) !== 3) {
            return false;
        }
        $year = $dateArray[2];
        $month = $dateArray[0];
        $day = $dateArray[1];

        $result = checkdate($month, $day, $year);
        if ($result) {
            return $format;
        } else {
            return false;
        }
    }


}
