<?php

declare(strict_types=1);

/*
 * This file is part of the monorepo package.
 *
 *     (c) Anthonius Munthi <https://itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monorepo\Test;

trait JsonConfigFileTrait
{
    /**
     * @param bool $override Override existing config
     *
     * @return string Generated json config file name
     */
    public function generateJsonConfig1($override = false)
    {
        $tmpDir = $this->getTempDir();
        $file   = $tmpDir.'/test1.json';

        if (!is_file($file) || $override) {
            $json   = <<<'JSON'
[
    {
        "name": "root1",
        "origin": "{$tmpDir}/root1/.git",
        "prefixes": [
            {
                "key": "src/sub1",
                "target": "{$tmpDir}/sub1/.git" 
            },
            {
                "key": "src/sub2",
                "target": "{$tmpDir}/sub2/.git"
            }
        ],
        "branches": ["develop","master"]
    },
    {
        "name": "root2",
        "origin": "{$tmpDir}/root2/.git",
        "prefixes": [
            {
                "key": "lib/sub1",
                "target": "{$tmpDir}/sub1/.git"
            },
            {
                "key": "lib/sub2",
                "target": "{$tmpDir}/sub2/.git"
            }
        ],
        "ignored-tags": "v1.0.*"
    }
]
JSON;

            file_put_contents($file, $json, LOCK_EX);
        }

        return $file;
    }

    public function getTempDir()
    {
        $dir = sys_get_temp_dir().'/monorepo/tests/config';
        @mkdir($dir, 0777, true);

        return $dir;
    }
}
