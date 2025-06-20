<?php
/**
 * This file is part of the Monolog Cascade package.
 *
 * (c) Raphael Antonmattei <rantonmattei@theorchard.com>
 * (c) The Orchard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cascade\Tests\Config\Loader\ClassLoader\Resolver;

use Cascade\Config\Loader\ClassLoader\Resolver\ConstructorResolver;
use Cascade\Util;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionParameter;
use Symfony;

/**
 * Class ConstructorResolverTest
 *
 * @author Raphael Antonmattei <rantonmattei@theorchard.com>
 */
class ConstructorResolverTest extends TestCase
{
    /**
     * Reflection class for which you want to resolve extra options
     * @var ReflectionClass
     */
    protected $reflected;

    /**
     * Constructor Resolver
     * @var ConstructorResolver
     */
    protected $resolver;
    private $class;

    /**
     * Set up function
     */
    protected function setUp(): void
    {
        $this->class = 'Cascade\Tests\Fixtures\SampleClass';
        $this->resolver = new ConstructorResolver(new ReflectionClass($this->class));
        parent::setUp();
    }

    /**
     * Tear down function
     */
    protected function tearDown(): void
    {
        $this->resolver = null;
        $this->class = null;
        parent::tearDown();
    }

    /**
     * Return the contructor args of the reflected class
     *
     * @return ReflectionParameter[] array of params
     */
    protected function getConstructorArgs()
    {
        return $this->resolver->getReflected()->getConstructor()->getParameters();
    }

    /**
     * Test the resolver contructor
     */
    public function testConstructor()
    {
        $this->assertEquals($this->class, $this->resolver->getReflected()->getName());
    }

    /**
     * Test that constructor args were pulled properly
     *
     * Note that we need to deuplicate the CamelCase conversion here for old
     * fashioned classes
     */
    public function testInitConstructorArgs()
    {
        $expectedConstructorArgs = array();

        foreach ($this->getConstructorArgs() as $param) {
            $expectedConstructorArgs[Util::snakeToCamelCase($param->getName())] = $param;
        }
        $this->assertEquals($expectedConstructorArgs, $this->resolver->getConstructorArgs());
    }

    /**
     * Test the hashToArgsArray function
     */
    public function testHashToArgsArray()
    {
        $this->assertEquals(
            array('someValue', 'hello', 'there', 'slither'),
            $this->resolver->hashToArgsArray(
                array( // Not properly ordered on purpose
                    'optionalB'     => 'there',
                    'optionalA'     => 'hello',
                    'optionalSnake' => 'slither',
                    'mandatory'     => 'someValue',
                )
            )
        );
    }

    /**
     * Data provider for testResolve
     *
     * The order of the input options does not matter and is somewhat random. The resolution
     * should reconcile those options and match them up with the contructor param position
     *
     * @return array of arrays with expected resolved values and options used as input
     */
    public static function optionsProvider()
    {
        return array(
            array(
                array('someValue', 'hello', 'there', 'slither'), // Expected resolved options
                array( // Options (order should not matter, part of resolution)
                    'optionalB'      => 'there',
                    'optionalA'      => 'hello',
                    'mandatory'      => 'someValue',
                    'optionalSnake'  => 'slither',
                )
            ),
            array(
                array('someValue', 'hello', 'BBB', 'snake'),
                array(
                    'mandatory' => 'someValue',
                    'optionalA' => 'hello',
                )
            ),
            array(
                array('someValue', 'AAA', 'BBB', 'snake'),
                array('mandatory' => 'someValue')
            )
        );
    }

    /**
     * Test resolving with valid options
     *
     * @param array $expectedResolvedOptions Array of expected resolved options
     * (i.e. parsed and validated)
     * @param  array $options Array of raw options
     * @dataProvider optionsProvider
     */
    public function testResolve(array $expectedResolvedOptions, array $options)
    {
        $this->assertEquals($expectedResolvedOptions, $this->resolver->resolve($options));
    }

    /**
     * Data provider for testResolveWithInvalidOptions.
     *
     * The order of the input options does not matter and is somewhat random. The resolution
     * should reconcile those options and match them up with the contructor param position
     *
     * @return array of arrays with expected resolved values and options used as input
     */
    public static function missingOptionsProvider()
    {
        return array(
            array(
                array( // No values
                ),
                array( // Missing a mandatory value
                    'optionalB' => 'BBB'
                ),
                array( // Still missing a mandatory value
                    'optionalB' => 'there',
                    'optionalA' => 'hello'
                )
            )
        );
    }

    /**
     * Test resolving with missing/incomplete options. It should throw an exception.
     *
     * @param  array $incompleteOptions Array of invalid options
     * @dataProvider missingOptionsProvider
     */
    public function testResolveWithMissingOptions(array $incompleteOptions)
    {
        $this->expectException(Symfony\Component\OptionsResolver\Exception\MissingOptionsException::class);
        $this->resolver->resolve($incompleteOptions);
    }

    /**
     * Data provider for testResolveWithInvalidOptions
     *
     * The order of the input options does not matter and is somewhat random. The resolution
     * should reconcile those options and match them up with the contructor param position
     *
     * @return array of arrays with expected resolved values and options used as input
     */
    public static function invalidOptionsProvider()
    {
        return array(
            array(
                array('ABC'),
                array( // All invalid
                    'someInvalidOptionA' => 'abc',
                    'someInvalidOptionB' => 'def'
                ),
                array( // Some invalid
                    'optionalB' => 'there',
                    'optionalA' => 'hello',
                    'mandatory' => 'dsadsa',
                    'additionalInvalid' => 'some unknow param'
                )
            )
        );
    }

    /**
     * Test resolving with invalid options. It should throw an exception.
     *
     * @param  array $invalidOptions Array of invalid options
     * @dataProvider invalidOptionsProvider
     */
    public function testResolveWithInvalidOptions($invalidOptions)
    {
        $this->expectException(Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException::class);
        $this->resolver->resolve($invalidOptions);
    }
}
