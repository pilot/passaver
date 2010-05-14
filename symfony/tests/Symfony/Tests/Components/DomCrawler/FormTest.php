<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Components\DomCrawler;

use Symfony\Components\DomCrawler\Form;

class FormTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorThrowsExceptionIfTheNodeHasNoFormAncestor()
    {
        $dom = new \DOMDocument();
        $dom->loadHTML('
            <html>
                <input type="submit" />
                <form>
                    <input type="foo" />
                </form>
                <button />
            </html>
        ');

        $nodes = $dom->getElementsByTagName('input');

        try
        {
            $form = new Form($nodes->item(0));
            $this->fail('__construct() throws a \\LogicException if the node has no form ancestor');
        }
        catch (\LogicException $e)
        {
            $this->assertTrue(true, '__construct() throws a \\LogicException if the node has no form ancestor');
        }

        try
        {
            $form = new Form($nodes->item(1));
            $this->fail('__construct() throws a \\LogicException if the input type is not submit, button, or image');
        }
        catch (\LogicException $e)
        {
            $this->assertTrue(true, '__construct() throws a \\LogicException if the input type is not submit, button, or image');
        }

        $nodes = $dom->getElementsByTagName('button');

        try
        {
            $form = new Form($nodes->item(0));
            $this->fail('__construct() throws a \\LogicException if the input type is not submit, button, or image');
        }
        catch (\LogicException $e)
        {
            $this->assertTrue(true, '__construct() throws a \\LogicException if the input type is not submit, button, or image');
        }
    }

    /**
     * @dataProvider provideInitializeValues
     */
    public function testConstructor($message, $form, $values)
    {
        $form = $this->createForm('<form>'.$form.'</form>');
        $this->assertEquals($values, array_map(function ($field) { return array(get_class($field), $field->getValue()); }, $form->getFields()), '->getDefaultValues() '.$message);
    }

    public function provideInitializeValues()
    {
        return array(
            array(
                'does not take into account input fields without a name attribute',
                '<input type="text" value="foo" />
                 <input type="submit" />',
                array(),
            ),
            array(
                'does not take into account disabled input fields',
                '<input type="text" name="foo" value="foo" disabled="disabled" />
                 <input type="submit" />',
                array(),
            ),
            array(
                'appends the submitted button value',
                '<input type="submit" name="bar" value="bar" />',
                array('bar' => array('Symfony\\Components\\DomCrawler\\Field\\InputFormField', 'bar')),
            ),
            array(
                'appends the submitted button value but not other submit buttons',
                '<input type="submit" name="bar" value="bar" />
                 <input type="submit" name="foobar" value="foobar" />',
                 array('foobar' => array('Symfony\\Components\\DomCrawler\\Field\\InputFormField', 'foobar')),
            ),
            array(
                'returns textareas',
                '<textarea name="foo">foo</textarea>
                 <input type="submit" />',
                 array('foo' => array('Symfony\\Components\\DomCrawler\\Field\\TextareaFormField', 'foo')),
            ),
            array(
                'returns inputs',
                '<input type="text" name="foo" value="foo" />
                 <input type="submit" />',
                 array('foo' => array('Symfony\\Components\\DomCrawler\\Field\\InputFormField', 'foo')),
            ),
            array(
                'returns checkboxes',
                '<input type="checkbox" name="foo" value="foo" checked="checked" />
                 <input type="submit" />',
                 array('foo' => array('Symfony\\Components\\DomCrawler\\Field\\ChoiceFormField', 'foo')),
            ),
            array(
                'returns not-checked checkboxes',
                '<input type="checkbox" name="foo" value="foo" />
                 <input type="submit" />',
                 array('foo' => array('Symfony\\Components\\DomCrawler\\Field\\ChoiceFormField', false)),
            ),
            array(
                'returns radio buttons',
                '<input type="radio" name="foo" value="foo" />
                 <input type="radio" name="foo" value="bar" checked="bar" />
                 <input type="submit" />',
                 array('foo' => array('Symfony\\Components\\DomCrawler\\Field\\ChoiceFormField', 'bar')),
            ),
            array(
                'returns file inputs',
                '<input type="file" name="foo" />
                 <input type="submit" />',
                 array('foo' => array('Symfony\\Components\\DomCrawler\\Field\\FileFormField', array('name' => '', 'type' => '', 'tmp_name' => '', 'error' => 4, 'size' => 0))),
            ),
        );
    }

    public function testGetFormNode()
    {
        $dom = new \DOMDocument();
        $dom->loadHTML('<html><form><input type="submit" /></form></html>');

        $form = new Form($dom->getElementsByTagName('input')->item(0));

        $this->assertSame($dom->getElementsByTagName('form')->item(0), $form->getFormNode(), '->getFormNode() returns the form node associated with this form');
    }

    public function testGetMethod()
    {
        $form = $this->createForm('<form><input type="submit" /></form>');
        $this->assertEquals('get', $form->getMethod(), '->getMethod() returns get if no method is defined');

        $form = $this->createForm('<form method="post"><input type="submit" /></form>');
        $this->assertEquals('post', $form->getMethod(), '->getMethod() returns the method attribute value of the form');

        $form = $this->createForm('<form method="post"><input type="submit" /></form>', 'put');
        $this->assertEquals('put', $form->getMethod(), '->getMethod() returns the method defined in the constructor if provided');
    }

    public function testGetSetValue()
    {
        $form = $this->createForm('<form><input type="text" name="foo" value="foo" /><input type="submit" /></form>');

        $this->assertEquals('foo', $form->getValue('foo'), '->getValue() returns the value of a form field');

        $ret = $form->setValue('foo', 'bar');

        $this->assertEquals($form, $ret, '->setValue() implements a fluent interface');
        $this->assertEquals('bar', $form->getValue('foo'), '->setValue() changes the value of a form field');

        try
        {
            $form->setValue('foobar', 'bar');
            $this->pass('->setValue() throws an \InvalidArgumentException exception if the field does not exist');
        }
        catch (\InvalidArgumentException $e)
        {
            $this->assertTrue(true, '->setValue() throws an \InvalidArgumentException exception if the field does not exist');
        }

        try
        {
            $form->getValue('foobar');
            $this->pass('->getValue() throws an \InvalidArgumentException exception if the field does not exist');
        }
        catch (\InvalidArgumentException $e)
        {
            $this->assertTrue(true, '->getValue() throws an \InvalidArgumentException exception if the field does not exist');
        }
    }

    public function testGetValues()
    {
        $form = $this->createForm('<form><input type="text" name="foo[bar]" value="foo" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>');
        $this->assertEquals(array('foo[bar]' => 'foo', 'bar' => 'bar'), $form->getValues(), '->getValues() returns all form field values');

        $form = $this->createForm('<form><input type="checkbox" name="foo" value="foo" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>');
        $this->assertEquals(array('bar' => 'bar'), $form->getValues(), '->getValues() does not include not-checked checkboxes');

        $form = $this->createForm('<form><input type="file" name="foo" value="foo" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>');
        $this->assertEquals(array('bar' => 'bar'), $form->getValues(), '->getValues() does not include file input fields');
    }

    public function testSetValues()
    {
        $form = $this->createForm('<form><input type="checkbox" name="foo" value="foo" checked="checked" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>');
        $form->setValues(array('foo' => false, 'bar' => 'foo'));
        $this->assertEquals(array('bar' => 'foo'), $form->getValues(), '->setValues() sets the values of fields');
    }

    public function testGetPhpValues()
    {
        $form = $this->createForm('<form><input type="text" name="foo[bar]" value="foo" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>');
        $this->assertEquals(array('foo' => array('bar' => 'foo'), 'bar' => 'bar'), $form->getPhpValues(), '->getPhpValues() converts keys with [] to arrays');
    }

    public function testGetFiles()
    {
        $form = $this->createForm('<form><input type="file" name="foo[bar]" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>');
        $this->assertEquals(array(), $form->getFiles(), '->getFiles() returns an empty array if method is get');

        $form = $this->createForm('<form method="post"><input type="file" name="foo[bar]" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>');
        $this->assertEquals(array('foo[bar]' => array('name' => '', 'type' => '', 'tmp_name' => '', 'error' => 4, 'size' => 0)), $form->getFiles(), '->getFiles() only returns file fields');
    }

    public function testGetPhpFiles()
    {
        $form = $this->createForm('<form method="post"><input type="file" name="foo[bar]" /><input type="text" name="bar" value="bar" /><input type="submit" /></form>');
        $this->assertEquals(array('foo' => array('bar' => array('name' => '', 'type' => '', 'tmp_name' => '', 'error' => 4, 'size' => 0))), $form->getPhpFiles(), '->getPhpFiles() converts keys with [] to arrays');
    }

    /**
     * @dataProvider provideGetUriValues
     */
    public function testGetUri($message, $form, $values, $uri)
    {
        $form = $this->createForm($form);
        $form->setValues($values);

        $this->assertEquals($uri, $form->getUri(), '->getUri() '.$message);
    }

    public function testGetUriAbsolute()
    {
        $form = $this->createForm('<form action="foo"><input type="submit" /></form>', null, 'http://localhost', '/foo/');
        $this->assertEquals('http://localhost/foo/foo', $form->getUri(true), '->getUri() returns absolute URIs');

        $form = $this->createForm('<form action="/foo"><input type="submit" /></form>', null, 'http://localhost', '/foo/');
        $this->assertEquals('http://localhost/foo', $form->getUri(true), '->getUri() returns absolute URIs');

        $form = $this->createForm('<form action="/foo"><input type="submit" /></form>');
        $this->assertEquals('/foo', $form->getUri(true), '->getUri() returns absolute URIs only if the host has been defined in the constructor');

        $form = $this->createForm('<form action="foo"><input type="submit" /></form>');
        $this->assertEquals('/foo', $form->getUri(true), '->getUri() returns absolute URIs only if the host has been defined in the constructor');
    }

    public function provideGetUriValues()
    {
        return array(
            array(
                'returns the URI of the form',
                '<form action="/foo"><input type="submit" /></form>',
                array(),
                '/foo'
            ),
            array(
                'appends the form values if the method is get',
                '<form action="/foo"><input type="text" name="foo" value="foo" /><input type="submit" /></form>',
                array(),
                '/foo?foo=foo'
            ),
            array(
                'appends the form values and merges the submitted values',
                '<form action="/foo"><input type="text" name="foo" value="foo" /><input type="submit" /></form>',
                array('foo' => 'bar'),
                '/foo?foo=bar'
            ),
            array(
                'does not append values if the method is post',
                '<form action="/foo" method="post"><input type="text" name="foo" value="foo" /><input type="submit" /></form>',
                array(),
                '/foo'
            ),
            array(
                'appends the form values to an existing query string',
                '<form action="/foo?bar=bar"><input type="text" name="foo" value="foo" /><input type="submit" /></form>',
                array(),
                '/foo?bar=bar&foo=foo'
            ),
            array(
                'returns an empty URI if the action is empty',
                '<form><input type="submit" /></form>',
                array(),
                '', 
            ),
            array(
                'appends the form values even if the action is empty',
                '<form><input type="text" name="foo" value="foo" /><input type="submit" /></form>',
                array(),
                '/?foo=foo',
            ),
        );
    }

    public function testHasField()
    {
        $form = $this->createForm('<form method="post"><input type="text" name="bar" value="bar" /><input type="submit" /></form>');

        $this->assertFalse($form->hasField('foo'), '->hasField() returns false if a field is not in the form');
        $this->assertTrue($form->hasField('bar'), '->hasField() returns true if a field is in the form');
    }

    public function testGetField()
    {
        $form = $this->createForm('<form method="post"><input type="text" name="bar" value="bar" /><input type="submit" /></form>');

        $this->assertEquals('Symfony\\Components\\DomCrawler\\Field\\InputFormField', get_class($form->getField('bar')), '->getField() returns the field object associated with the given name');

        try
        {
            $form->getField('foo');
            $this->fail('->getField() throws an \InvalidArgumentException if the field does not exist');
        }
        catch (\InvalidArgumentException $e)
        {
            $this->assertTrue(true, '->getField() throws an \InvalidArgumentException if the field does not exist');
        }
    }

    public function testGetFields()
    {
        $form = $this->createForm('<form method="post"><input type="text" name="bar" value="bar" /><input type="submit" /></form>');

        $fields = $form->getFields();
        $this->assertEquals(1, count($fields), '->getFields() return an array of form field objects');
        $this->assertEquals('Symfony\\Components\\DomCrawler\\Field\\InputFormField', get_class($fields['bar']), '->getFields() return an array of form field objects');
    }

    protected function createForm($form, $method = null, $host = null, $path = '/')
    {
        $dom = new \DOMDocument();
        $dom->loadHTML('<html>'.$form.'</html>');

        $nodes = $dom->getElementsByTagName('input');

        return new Form($nodes->item($nodes->length - 1), $method, $host, $path);
    }
}
