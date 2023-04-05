<?php

    namespace Core\ML;

    /**
     * Класс нейрона
     */
    class Neuron
    {

        /** @var int Идентификатор нейрона */
        private int $id;

        /** @var float|null Текущее значение */
        private ?float $value;

        /**
         * Конструктор
         *
         * @param int $id Идентификатор нейрона
         */
        public function __construct(int $id)
        {
            $this->id = $id;
        }

        /** Получить текущее значение */
        public function getValue(): ?float
        {
            return $this->value;
        }

        /** Задать текущее значение */
        public function setValue(float $value): self
        {
            $this->value = $value;
            return $this;
        }
    }
