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

    namespace Core\DataObjects;

    use \IteratorAggregate;
    use \JsonSerializable;
    use \ArrayIterator;

    /**
     * Абстрактная коллекция элементов
     */
    abstract class AbstractCollection implements IteratorAggregate, JsonSerializable
    {
        /** @var array Список элементов */
        protected $collection = [];

        /** @var string Сортировка по возрастанию */
        public const SORT_ASC = 'ASC';

        /** @var string Сортировка по убыванию */
        public const SORT_DESC = 'DESC';

        /**
         * Проверка коллекции на пустоту
         *
         * @return bool
         */
        public function isEmpty(): bool
        {
            return empty($this->collection);
        }

        /**
         * Добавление элемента в коллекцию
         *
         * @param mixed $element Элемент
         */
        public function add($element): void
        {
            $this->collection[] = $element;
        }

        /**
         * Получить итератор коллекции
         *
         * @return ArrayIterator
         */
        public function getIterator(): ArrayIterator
        {
            return new ArrayIterator($this->collection);
        }

        /**
         * Получить первый элемент коллекции
         *
         * @return mixed
         */
        public function first()
        {
            return array_shift($this->collection);
        }

        /**s
         * Сортировка коллекции
         *
         * @param string $by   Поле по которому необходимо осуществить сортировку
         * @param string $sort Направление сортировки (ASC, DESC)
         *
         * @return void
         */
        public function sort(string $by, string $sort): void
        {
            uasort(
                $this->collection, function ($a, $b) use ($by, $sort) {
                if ($sort === self::SORT_ASC) {
                    return ($a->{$by} < $b->{$by}) ? -1 : 1;
                }
                if ($sort === self::SORT_DESC) {
                    return ($a->{$by} > $b->{$by}) ? -1 : 1;
                }
                return 0;
            }
            );
        }

        /**
         * Получение количества элементов в коллекции
         *
         * @return int
         */
        public function getCount(): int
        {
            return count($this->collection);
        }

        /**
         * Очистка коллекции
         */
        public function clear(): void
        {
            $this->collection = [];
        }

        /**
         * Объединение двух коллекций в одну
         *
         * @param AbstractCollection $collection Коллекция, которую нужно добавить в текущую
         *
         * @return $this
         */
        public function mergeCollection(AbstractCollection $collection): self
        {
            $this->collection = array_merge($this->collection, $collection->getIterator()->getArrayCopy());
            return $this;
        }

        /**
         * Сериализация в JSON
         *
         * @return array
         */
        public function jsonSerialize(): array
        {
            return $this->collection;
        }
    }
