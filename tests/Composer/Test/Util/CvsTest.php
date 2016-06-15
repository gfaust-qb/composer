<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Test\Util;

use Composer\Config;
use Composer\IO\NullIO;
use Composer\Util\Platform;
use Composer\Util\Cvs;

class CvsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test the credential string.
     *
     * @param string $url    The CVS url.
     * @param string $expect The expectation for the test.
     *
     * @dataProvider urlProvider
     */
    public function testCredentials($url, $expect)
    {
        $cvs = new Cvs($url, new NullIO, new Config());
        $reflMethod = new \ReflectionMethod('Composer\\Util\\Cvs', 'getCredentialString');
        $reflMethod->setAccessible(true);

        $this->assertEquals($expect, $reflMethod->invoke($cvs));
    }

    /**
     * Provide some examples for {@self::testCredentials()}.
     *
     * @return array
     */
    public function urlProvider()
    {
        return array(
            array('http://till:test@cvs.example.org/', $this->getCmd(" --username 'till' --password 'test' ")),
            array('http://cvs.apache.org/', ''),
            array('cvs://johndoe@example.org', $this->getCmd(" --username 'johndoe' --password '' ")),
        );
    }

    public function testInteractiveString()
    {
        $url = 'http://cvs.example.org';

        $cvs = new Cvs($url, new NullIO(), new Config());
        $reflMethod = new \ReflectionMethod('Composer\\Util\\Cvs', 'getCommand');
        $reflMethod->setAccessible(true);

        $this->assertEquals(
            $this->getCmd("cvs ls --non-interactive  'http://cvs.example.org'"),
            $reflMethod->invokeArgs($cvs, array('cvs ls', $url))
        );
    }

    public function testCredentialsFromConfig()
    {
        $url = 'http://cvs.apache.org';

        $config = new Config();
        $config->merge(array(
            'config' => array(
                'http-basic' => array(
                    'cvs.apache.org' => array('username' => 'foo', 'password' => 'bar'),
                ),
            ),
        ));

        $cvs = new Cvs($url, new NullIO, $config);
        $reflMethod = new \ReflectionMethod('Composer\\Util\\Cvs', 'getCredentialString');
        $reflMethod->setAccessible(true);

        $this->assertEquals($this->getCmd(" --username 'foo' --password 'bar' "), $reflMethod->invoke($cvs));
    }

    public function testCredentialsFromConfigWithCacheCredentialsTrue()
    {
        $url = 'http://cvs.apache.org';

        $config = new Config();
        $config->merge(
            array(
                'config' => array(
                    'http-basic' => array(
                        'cvs.apache.org' => array('username' => 'foo', 'password' => 'bar'),
                    ),
                ),
            )
        );

        $cvs = new Cvs($url, new NullIO, $config);
        $cvs->setCacheCredentials(true);
        $reflMethod = new \ReflectionMethod('Composer\\Util\\Cvs', 'getCredentialString');
        $reflMethod->setAccessible(true);

        $this->assertEquals($this->getCmd(" --username 'foo' --password 'bar' "), $reflMethod->invoke($cvs));
    }

    public function testCredentialsFromConfigWithCacheCredentialsFalse()
    {
        $url = 'http://cvs.apache.org';

        $config = new Config();
        $config->merge(
            array(
                'config' => array(
                    'http-basic' => array(
                        'cvs.apache.org' => array('username' => 'foo', 'password' => 'bar'),
                    ),
                ),
            )
        );

        $cvs = new Cvs($url, new NullIO, $config);
        $cvs->setCacheCredentials(false);
        $reflMethod = new \ReflectionMethod('Composer\\Util\\Cvs', 'getCredentialString');
        $reflMethod->setAccessible(true);

        $this->assertEquals($this->getCmd(" --no-auth-cache --username 'foo' --password 'bar' "), $reflMethod->invoke($cvs));
    }

    private function getCmd($cmd)
    {
        return Platform::isWindows() ? strtr($cmd, "'", '"') : $cmd;
    }
}
