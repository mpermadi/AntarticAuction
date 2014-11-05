# Test-Driven Development Helpers and Adapters

The packages provides two adapters for globally defined functions and variables and a static mocker class.  
The adapters answer to my need for a way to apply TDD techiques to WordPress plugin and theme development. 

## Adapters

### Functions adapter
Wraps call to globally defined functions in a method call. If <code>some_function</code> is a function defined in the global scope then a call to it could be made using the adapter like

    $adapter = new tad_FunctionsAdapter();
    $var = $adapter->some_function();

the adapter uses an interface for more flexible mocking in tests like

    $mockF = $this->getMock('tad_FunctionsAdapterInterface');

### Globals adapter
Allows superglobals to be accessed via an object method fashion.  
Usage example to access <code>$GLOBALS['foo']</code> is

    $g = new tad_GlobalsAdapter();
    $foo = $g->globals('foo');

To get the superglobal array call the function with no arguments, i.e.
to get the <code>$_SERVER</code> array

    $g = new tad_GlobalsAdapter();
    $g->server();

## Static Mocker
A test helper to mock (in a way and with limits) static method calls.  
Tested class should allow for class name injection like

    public function __construct($var1, $var2, $util = 'StaticClass')
    {
        $this->util = $util;

        $var = $this->util::doSomething();
    }

and then in the test file

    class StaticClass extends tad_StaticMocker
    {}


    class ClassNameTest extends \PHPUnit_Framework_TestCase
    {
        public function test_construct_calls_static_class_doSomething()
        {
            // Create a stub for the SomeClass class.
            $stub = $this->getMock('SomeClass');

            // Configure the stub.
            $stub->expects($this->any())
                ->method('doSomething')
                ->will($this->returnValue('foo'));

            StaticClass::_setListener($stub);

            $sut = new ClassName('some', 'var', 'StaticClass');
        }
    }

## Testable objects
The <code>abstract</code> class <code>tad_TestableObject</code> is meant to be used as a parent class to any class that's meant to be developed using TDD techniques allowing for quicker mocking of one or more method dependencies. Testing a class like

    class ClassOne{

        protected $d;

        public function __construct(D $d){
            $this->d = $d;
        }

        public function methodOne(A $a, BInterface $b, CInterface $c){
            $a->method();
            $b->method();
            $c->method();
            $this->d->method();
        }
    }

would require mocking instances of the <code>A</code> and <code>D</code> classes and the <code>BInterface</code> and <code>CInterface</code> interfaces like

    // file ClassOneTest.php

    public function test_methodOne_will_call_methods(){
        $mockA = $this->getMock('A', array('method'));
        $mockBInterface = $this->getMock('BInterface', array('method'));
        $mockCInterface = $this->getMock('CInterface', array('method'));
        $mockD = $this->getMock('D', array('method'));
     
        $mockA->expects($this->once())->method('method');
        $mockBInterface->expects($this->once())->method('method');
        $mockCInterface->expects($this->once())->method('method');
        $mockD->expects($this->once())->method('method');

        $sut = new ClassOne($mockD);

        $sut->methodOne();
    }

The <code>getMocks</code> method defined in the <code>tad_TestableObject</code> class allows, given a DocBlock comments is in place, to rewrite the class to be tested adding DocBlock comments

    class ClassOne extends tad_TestableObject {

        protected $d;
    
        /**
         * @depends D
         */
        public function __construct(D $d){
            $this->d = $d;
        }

        /**
         * @depends A, BInterface, CInterface
         */
        public function methodOne(A $a, BInterface $b, CInterface $c){
            $a->method();
            $b->method();
            $c->method();
            $this->d->method();
        }
    }

and to rewrite the test method to

    // file ClassOneTest.php

    public function test_methodOne_will_call_methods(){

        $mockedDependencies = ClassOne::getMocksFor(array('__construct', 'methodOne'))     

        $mockDependencies->A->expects($this->once())->method('method');
        $mockDependencies->BInterface->expects($this->once())->method('method');
        $mockDependencies->CInterface->expects($this->once())->method('method');
        $mockDependencies->D->expects($this->once())->method('method');

        $sut = new ClassOne($mockedDependencies->$mockD);

        $sut->methodOne();
    }

alternatively mocks can be retrieved in an array using the <code>getMocksArrayFor</code> method like

    // file ClassOneTest.php

    public function test_methodOne_will_call_methods(){

        extract(ClassOne::getMocksArrayFor(array('__construct', 'methodOne')));

        $A->expects($this->once())->method('method');
        $BInterface->expects($this->once())->method('method');
        $CInterface->expects($this->once())->method('method');
        $D->expects($this->once())->method('method');

        $sut = new ClassOne($D);

        $sut->methodOne();
    }

## tad_DependencyMocker
All of the above mocking is possible on classes not extending the <code>tad_TestableObject</code> class as well using the <code>tad_DependencyMocker</code> class. Given a class has its DocBlocks set up as above but it's not extending the <code>tad_TestableObject</code> class:

    class ClassOne {

        protected $d;
    
        /**
         * @depends D
         */
        public function __construct(D $d){
            $this->d = $d;
        }

        /**
         * @depends A, BInterface, CInterface
         */
        public function methodOne(A $a, BInterface $b, CInterface $c){
            $a->method();
            $b->method();
            $c->method();
            $this->d->method();
        }
    }

Then its method dependencies can still be mocked like

    // file ClassOneTest.php

    public function test_methodOne_will_call_methods(){
    
        $extraMethods = array(
                'BInterface' => array('fooMethod', 'bazMethod'),
                'CInterface' => array('barMethod')
            );

        extract(tad_DependencyMocker::on('ClassOne')
            ->forMethods(array('__construct', 'methodOne'))
            ->setExtraMethods($extraMethods)
            ->getMocksArray());

        $A->expects($this->once())->method('method');
        $BInterface->expects($this->once())->method('method');
        $BInterface->expects($this->once())->method('fooMethod');
        $BInterface->expects($this->once())->method('bazMethod');
        $CInterface->expects($this->once())->method('method');
        $CInterface->expects($this->once())->method('barMethod');
        $D->expects($this->once())->method('method');

        $sut = new ClassOne($D);

        $sut->methodOne();
    }

### Methods
The class defines the following methods:

* <code>__construct($className, $methodNameOrArray, $extraMethods, $notation)</code> - static, returns an instance of the class based on the specified class; aside for the <code>$className</code> all other arguments can be set later.
* <code>on($className, $methodNameOrArray, $extraMethods, $notation)</code> - static, returns an instance of the class based on the specified class; aside for the <code>$className</code> all other arguments can be set later.
* <code>forMethods($methodNameOrArray)</code> - sets the method or methods to be mocked by the class
* <code>setNotation($notation)</code> - sets the notation to be used for reading method meta information from the documentation block, by default the notation used is "depends"
* <code>setExtraMethods($extraMethods)</code> - sets an array of class/methods to be stubbed beside the ones the class already defines; in the example above <code>BInterface</code> gets two stubbed extra methods, <code>fooMethod</code> and <code>bazMethod</code> it would not implement.
* <code>getMocks()</code> - gets a <code>stdClass</code> object defining each dependency as a property acessible via the property "->" notation.
* <code>getMocksArray()</code> - gets an array containing each mocked dependency in a key/value pair.
