<?php

/**
 * Class tad_DependencyMocker
 *
 * Mocks method dependencies. The supposed workflow is
 *
 *     $mocker = new tad_DependencyMocker($this, $className);
 *     $mockedDependencies = $mocker->forMethods(array('methodOne, methodTwo));
 *
 *     // set expectations and return values on mocked objects
 *     $mockedDependencies->DependencyOne->expects(...
 */
class tad_DependencyMocker
{
    protected $className;
    protected $methodName;
    protected $notation;
    protected $extraMethods;

    /**
     * @param $className The class that should have its dependencies mocked.
     * @param string/array $methodNameOrArray The methods to mock the dependencies of.
     * @param array $extraMethods An associative array of class/methods that should be explicitly mocked.
     * @param string $notation The notation to use to parse method dependencies.
     */
    public function __construct($className, $methodNameOrArray = null, array $extraMethods = null, $notation = 'depends')
    {
        if (!is_string($className)) {
            throw new InvalidArgumentException('Class name must be a string', 1);
        }
        if (!class_exists($className)) {
            throw new InvalidArgumentException("Class $className does not exisit", 2);
        }
        $this->className = $className;
        if (isset($methodNameOrArray)) {
            $this->forMethods($methodNameOrArray);
        }
        if (isset($methods)) {
            $this->setMethods($methods);
        }
        if (isset($notation)) {
            $this->setNotation($notation);
        }
    }

    /**
     * Sets the notation to be used to pick up a method dependencies.
     *
     * By default the "depends" notation will be used.
     *
     * @param $notation
     * @return $this
     */
    public function setNotation($notation)
    {
        $this->notation = $notation;
        return $this;
    }

    /**
     * Returns an object defining each mocked dependency as a property.
     *
     * The property name is the same as the mocked class name.
     * If no method names are specified using the "forMethods"
     * method then the "__construct" method will be mocked.
     *
     * @return stdClass
     */
    public function getMocks()
    {
        return $this->getMocksObjectOrArray(true);
    }

    /**
     * Returns an array containing the mocked dependencies.
     *
     * The array format is ['ClassName' => mock]. If no method
     * names are specified using the "forMethods" method then
     * the "__construct" method will be mocked.
     *
     * @return array
     */
    public function getMocksArray()
    {
        return $this->getMocksObjectOrArray(false);
    }

    /**
     * Sets one or more methods to be mocked.
     *
     * @param $methodNameOrArray
     * @return $this
     */
    public function forMethods($methodNameOrArray)
    {
        if (!is_string($methodNameOrArray) && !is_array($methodNameOrArray)) {
            throw new InvalidArgumentException('Method name must be a string or an array', 1);
        }
        $this->methodName = $methodNameOrArray;
        return $this;
    }

    /**
     * @return stdClass/array
     */
    protected function getMocksObjectOrArray($getObject = true)
    {
        $notation = $this->notation ? '@' . $this->notation : '@depends';
        if (!isset($this->methodName)) {
            $methods = array('__construct');
        } else {
            $methods = is_array($this->methodName) ? $this->methodName : array($this->methodName);
        }
        $classes = array();
        foreach ($methods as $method) {
            $reflector = new ReflectionMethod($this->className, $method);
            $docBlock = $reflector->getDocComment();
            $lines = explode("\n", $docBlock);
            foreach ($lines as $line) {
                if (count($parts = explode($notation, $line)) > 1) {
                    $methodDependencies = trim(preg_replace("/[,;(; )(, )]+/", " ", $parts[1]));
                    $methodDependencies = explode(' ', $methodDependencies);
                    foreach ($methodDependencies as $class) {
                        $classes[] = $class;
                    }
                }
            }
        }

        $methods = array();
        $stubsForClasses = $this->extraMethods ? $this->extraMethods : array();
        array_map(function ($class) use (&$methods, $stubsForClasses) {
            $reflector = new ReflectionClass($class);
            $definedMethods = $reflector->getMethods(ReflectionMethod::IS_PUBLIC);
            $definedMethodNames = array_map(function ($method) {
                return $method->name;
            }, $definedMethods);
            $stubMethods = isset($stubsForClasses[$class]) ? $stubsForClasses[$class] : array();
            $methods[$class] = array_merge($definedMethodNames, $stubMethods);
        }, $classes);

        $testCase = new tad_SpoofTestCase();
        $mocks = new stdClass();
        foreach ($classes as $class) {
            $mocks->$class = $testCase
                ->getMockBuilder($class)
                ->disableOriginalConstructor()
                ->setMethods(array_unique($methods[$class]))
                ->getMock();
        }
        if ($getObject) {
            return $mocks;
        }
        return (array)$mocks;
    }

    /**
     * Static constructor method for the class.
     *
     * The method is will acccept the same parameters as the `__construct`
     * method and is meant as a fluent chain start.
     *
     * @param $className The class that should have its dependencies mocked.
     * @param string/array $methodNameOrArray The methods to mock the dependencies of.
     * @param array $extraMethods An associative array of class/methods that should be explicitly mocked.
     * @param string $notation The notation to use to parse method dependencies.
     * @return tad_DependencyMocker
     */
    public static function on($className, $methodNameOrArray = null, array $extraMethods = null, $notation = 'depends')
    {
        return new self($className);
    }

    /**
     * Sets the methods to be explicitly stubbed.
     *
     * The method is useful when stubbing classes that rely on magic methods
     * and that will, hence, expose no public methods. The array to is in the
     * format
     *
     *      [
     *          'ClassName' => ['methodOne', 'methodTwo', 'methodThree'],
     *          'ClassName2' => ['methodOne', 'methodTwo', 'methodThree']
     *      ]
     *
     * @param array $extraMethods a className to array of methods associative array.
     * @return $this
     */
    public function setExtraMethods(array $extraMethods)
    {
        $this->extraMethods = $extraMethods;
        return $this;
    }
}

/**
 * Class tad_SpoofTestCase
 *
 * Just an extension of the PHPUnit_Framework_TestCase class
 * to allow for method mocks creation.
 */
class tad_SpoofTestCase extends PHPUnit_Framework_TestCase
{

}