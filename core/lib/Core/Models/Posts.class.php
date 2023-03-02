<?php
    /**
     * Copyright (c) 2022 Roman Grinko <rsgrinko@gmail.com>
     * Permission is hereby granted, free of charge, to any person obtaining
     * a copy of this software and associated documentation files (the
     * "Software"), to deal in the Software without restriction, including
     * without limitation the rights to use, copy, modify, merge, publish,
     * distribute, sublicense, and/or sell copies of the Software, and to
     * permit persons to whom the Software is furnished to do so, subject to
     * the following conditions:
     * The above copyright notice and this permission notice shall be included
     * in all copies or substantial portions of the Software.
     * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
     * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
     * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
     * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
     * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
     * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
     * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
     */

    /**
     * Класс для работы с пользователями
     */

    namespace Core\Models;

    use Core\CoreException;
    use Core\Helpers\Sanitize;
    use Core\Models\DB;

    class Posts
    {
        private const TABLE = DB_TABLE_PREFIX . 'posts';

        private const TABLE_SECTIONS = DB_TABLE_PREFIX . 'sections';

        private $id = null;
        private $sectionId = null;

        public function __construct(?int $id = null)
        {
            if (!empty($id)) {
                $this->id = $id;
            }
        }

        public function setSection(int $sectionId): self
        {
            $this->sectionId = Sanitize::sanitizeNumber($sectionId);
            return $this;
        }

        public function getSection(): ?int
        {
            return $this->sectionId;
        }

        public function update(array $fields): bool
        {
            if(empty($this->id)){
                throw new CoreException('ID записи не задан');
            }
            foreach($fields as $key => $value) {
                $fields[$key] = Sanitize::sanitizeString($value);
            }
            /** @var  $DB DB */
            $DB = DB::getInstance();

            return $DB->update(self::TABLE, ['id' => $this->id], $fields);
        }

        public function getPosts(string $limit = '10', string $sort = 'DESC'): array
        {
            /** @var  $DB DB */
            $DB = DB::getInstance();

            $sql = 'SELECT * FROM ' . self::TABLE;
            if (!empty($this->sectionId)) {
                $sql .= ' WHERE section_id="' . $this->sectionId . '"';
            }

            $sql .= ' ORDER BY id ' . $sort . ' LIMIT ' . $limit;
            return $DB->query($sql) ?: [];
        }

        public function getAllPostData(): ?array
        {
            $result = (DB::getInstance())->getItem(self::TABLE, ['id' => $this->id]);

            if (!empty($result)) {
                return $result;
            }

            return null;
        }

        public function getImage(): ?array
        {
            $result  = null;
            $imageId = $this->getAllPostData()['image_id'];

            if (!empty($imageId)) {
                try {
                    $result = (new File($imageId))->getAllProps();
                } catch (CoreException $e) {
                }
            }

            return $result;
        }

        public function getAllCount(): int
        {
            /** @var  $DB DB */
            $DB = DB::getInstance();

            $sql = 'SELECT count(*) as count FROM ' . self::TABLE;
            if (!empty($this->sectionId)) {
                $sql .= ' WHERE section_id = "' . $this->sectionId . '"';
            }
            $res = $DB->query($sql);
            return  $res ? (int)$res[0]['count'] : 0;
        }

        public function getMainSections(): array
        {
            /** @var  $DB DB */
            $DB = DB::getInstance();

            $res = $DB->query('SELECT id, name, description, image_id FROM ' . self::TABLE_SECTIONS .' WHERE parrent_id = 0');
            return  $res ?: [];
        }


    }