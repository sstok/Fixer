<?php

/**
 * This file is part of Laravel Fixer by Graham Campbell.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at http://bit.ly/UWsjkb.
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace GrahamCampbell\Fixer;

use GrahamCampbell\Fixer\Analysers\Analyser;
use GrahamCampbell\Fixer\GitHub\Repository;
use GrahamCampbell\Fixer\Models\Repo;

/**
 * This is the fixer class.
 *
 * @author    Graham Campbell <graham@mineuk.com>
 * @copyright 2014 Graham Campbell
 * @license   <https://github.com/GrahamCampbell/Laravel-Fixer/blob/master/LICENSE.md> Apache 2.0
 */
class Fixer
{
    /**
     * The analyser instance.
     *
     * @var \GrahamCampbell\Fixer\Analysers\Analyser
     */
    protected $analyser;

    /**
     * The storage path.
     *
     * @var string
     */
    protected $path;

    /**
     * The gitlib options.
     *
     * @var array
     */
    protected $options;

    /**
     * Create a fixer instance.
     *
     * @param \GrahamCampbell\Fixer\Analysers\Analyser $analyser
     * @param string                                   $path
     * @param array                                    $options
     *
     * @return void
     */
    public function __construct(Analyser $analyser, $path, array $options)
    {
        $this->analyser = $analyser;
        $this->path = $path;
        $this->options = $options;
    }

    /**
     * Analyse the commit and save the results.
     *
     * @param string $repo
     * @param string $commit
     *
     * @return \GrahamCampbell\Fixer\Models\Commit
     */
    public function analyse($repo, $commit)
    {
        $this->setup($repo, $commit);

        $path = $this->path.'/'.sha1($repo);
        $data = $this->analyser->analyse($path);

        $repo = Repo::firstOrCreate(['id' => sha1($repo), 'name' => $repo]);
        $commit = $repo->commits()->create(['id' => $commit, 'time' => $data['time'], 'memory' => $data['memory']]);
        $commit->files()->createMany($data['files']);

        return $commit;
    }

    /**
     * Set things up for analysis.
     *
     * @param string $repo
     * @param string $commit
     *
     * @return void
     */
    protected function setup($repo, $commit)
    {
        $repo = new Repository($repo, $this->path, $this->options);

        if (!$repo->exists()) {
            $repo->get();
        }

        $repo->fetch();

        $repo->reset($commit);
    }
}