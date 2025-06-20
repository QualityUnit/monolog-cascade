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
namespace Cascade\Tests\Config\Loader\FileLoader;

use Cascade\Tests\Fixtures;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockBuilder;
use stdClass;
use Symfony\Component\Yaml\Yaml as YamlParser;

/**
 * Class YamlTest
 *
 * @author Raphael Antonmattei <rantonmattei@theorchard.com>
 */
class YamlTest extends TestCase
{
    /**
     * Yaml loader mock builder
     * @var PHPUnit_Framework_MockObject_MockBuilder
     */
    protected $yamlLoader = null;

    protected function setUp(): void
    {
        parent::setUp();

        $fileLocatorMock = $this->createMock(
            'Symfony\Component\Config\FileLocatorInterface'
        );

        $this->yamlLoader = $this->getMockBuilder(
            'Cascade\Config\Loader\FileLoader\Yaml'
        )
            ->setConstructorArgs(array($fileLocatorMock))
            ->onlyMethods(array('readFrom', 'isFile', 'validateExtension'))
            ->getMock();
    }

    protected function tearDown(): void
    {
        $this->yamlLoader = null;
        parent::tearDown();
    }

    /**
     * Test loading a Yaml string
     */
    public function testLoad()
    {
        $yaml = Fixtures::getSampleYamlString();

        $this->yamlLoader->expects($this->once())
            ->method('readFrom')
            ->willReturn($yaml);

        $this->assertEquals(
            YamlParser::parse($yaml),
            $this->yamlLoader->load($yaml)
        );
    }

    /**
     * Data provider for testSupportsWithInvalidResource
     * @return array array non-string values
     */
    public static function notStringDataProvider()
    {
        return array(
            array(array()),
            array(true),
            array(123),
            array(123.456),
            array(null),
            array(new stdClass),
            // array(function () {
            // })
            // cannot test Closure type because of PhpUnit
            // @see https://github.com/sebastianbergmann/phpunit/issues/451
        );
    }

    /**
     * Test loading resources supported by the YamlLoader
     *
     * @param mixed $invalidResource Invalid resource value
     * @dataProvider notStringDataProvider
     */
    public function testSupportsWithInvalidResource($invalidResource)
    {
        $this->assertFalse($this->yamlLoader->supports($invalidResource));
    }

    /**
     * Test loading a Yaml string
     */
    public function testSupportsWithYamlString()
    {
        $this->yamlLoader->expects($this->once())
            ->method('isFile')
            ->willReturn(false);

        $yaml = Fixtures::getSampleYamlString();

        $this->assertTrue($this->yamlLoader->supports($yaml));
    }

    /**
     * Test loading a Yaml file
     */
    public function testSupportsWithYamlFile()
    {
        $this->yamlLoader->expects($this->once())
            ->method('isFile')
            ->willReturn(true);

        $this->yamlLoader->expects($this->once())
            ->method('validateExtension')
            ->willReturn(true);

        $yamlFile = Fixtures::getSampleYamlFile();

        $this->assertTrue($this->yamlLoader->supports($yamlFile));
    }
}
