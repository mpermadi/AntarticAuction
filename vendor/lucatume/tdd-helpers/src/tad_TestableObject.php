<?php

/**
 * Class tad_TestableObject
 *
 * A class that's meant to be used as a parent class for objects developed using TDD techniques.
 */
abstract class tad_TestableObject
{
    /**
     * Gets the mocked dependencies for one or more of the class public methods
     * in an object.
     *
     * The extending class is required to define valid doc blocks
     * for each public method that's meant to have its dependencies
     * mocked using the "@depends" notation.
     * See tad_DependencyMocker class for more in-detail information.
     *
     *      * @depends A, B, CInterface
     *
     * @param PHPUnit_Framework_TestCase $testCase
     * @param $methodName Either a method name or an array of method names.
     * @return stdClass An object defining each mock as a property named as
     * the mocked class.
     */
    public static function getMocksFor($methodName)
    {
        return self::getMocksObjectOrArrayFor($methodName, true);
    }

    /**
     * Gets the mocked dependencies for one or more of the class public methods
     * in an array.
     *
     * The extending class is required to define valid doc blocks
     * for each public method that's meant to have its dependencies
     * mocked using the "@depends" notation.
     * See tad_DependencyMocker class for more in-detail information.
     *
     *      * @depends A, B, CInterface
     *
     * @param PHPUnit_Framework_TestCase $testCase
     * @param $methodName Either a method name or an array of method names.
     * @return array An array of mock objects each stored under a key named
     * as the mocked class name.
     */
    public static function getMocksArrayFor($methodName)
    {
        return self::getMocksObjectOrArrayFor($methodName, false);
    }

    protected static function getMocksObjectOrArrayFor($methodName, $returnObject = true)
    {

        if (!is_string($methodName)) {
            throw new InvalidArgumentException('Method name must be a string', 1);
        }
        if (!function_exists('get_called_class')) {
            throw new RuntimeException('While the class is PHP 5.2 compatible the getMocksFor method is meant to be used in testing environment based on PHP >= 5.3 version.', 2);
        }
        $className = get_called_class();
        if (!method_exists($className, $methodName)) {
            throw new InvalidArgumentException("Method $methodName does not exist", 3);
        }
        $mocker = new tad_DependencyMocker($className);
        if ($returnObject) {
            return $mocker->forMethods($methodName)
                ->getMocks();
        } else {
            return $mocker->forMethods($methodName)
                ->getMocksArray();
        }
    }
}