<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Components\Finder\Iterator;

use Symfony\Components\Finder\Iterator\FileTypeFilterIterator;

require_once __DIR__.'/RealIteratorTestCase.php';

class FileTypeFilterIteratorTest extends RealIteratorTestCase
{
    /**
     * @dataProvider getAcceptData
     */
    public function testAccept($mode, $expected)
    {
        $inner = new Iterator(self::$files);

        $iterator = new FileTypeFilterIterator($inner, $mode);

        $this->assertIterator($expected, $iterator);
    }

    public function getAcceptData()
    {
        return array(
            array(FileTypeFilterIterator::ONLY_FILES, array(sys_get_temp_dir().'/symfony2_finder/test.py', sys_get_temp_dir().'/symfony2_finder/foo/bar.tmp', sys_get_temp_dir().'/symfony2_finder/test.php')),
            array(FileTypeFilterIterator::ONLY_DIRECTORIES, array(sys_get_temp_dir().'/symfony2_finder/.git', sys_get_temp_dir().'/symfony2_finder/foo', sys_get_temp_dir().'/symfony2_finder/toto')),
        );
    }
}
