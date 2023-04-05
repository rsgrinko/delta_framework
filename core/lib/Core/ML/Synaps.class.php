<?php

    namespace Core\ML;

    /**
     * Класс синапса
     * является связующим звеном между нейронами
     */
    class Synaps
    {
        /** @var int Идентификатор синапса */
        private $id;

        /** @var float Вес */
        private $value;

        /** @var Neuron Входной нейрон */
        private Neuron $inNeuron;

        /** @var Neuron Выходной нейрон */
        private Neuron $outNeuron;

        /**
         * Конструктор
         *
         * @param int        $id        Идентификатор синапса
         * @param Neuron     $inNeuron  Входной нейрон
         * @param Neuron     $outNeuron Выходной нейрон
         * @param float|null $value     Значение веса синапса
         *
         * @throws \Exception
         */
        public function __construct(int $id, Neuron $inNeuron, Neuron $outNeuron, float $value = null)
        {
            $this->inNeuron  = $inNeuron;
            $this->outNeuron = $outNeuron;

            // если мы хотим явно задать вес - задаем, иначе генерируем как-нибудь
            if ($value !== null) {
                $this->value = $value;
            } else {
                $this->value = random_int(0, 100) / 100;
            }
            $this->id = $id;
        }

        /** Получить id синапса */
        public function getId(): int
        {
            return $this->id;
        }

        /**
         * Получение входящего нейрона
         *
         * @return Neuron
         */
        public function getInNeuron(): Neuron
        {
            return $this->inNeuron;
        }

        /**
         * Получение выходящего нейрона
         *
         * @return Neuron
         */
        public function getOutNeuron(): Neuron
        {
            return $this->outNeuron;
        }


        /**
         * Получить вес
         *
         * @return float Вес
         */
        public function getValue(): ?float
        {
            return $this->value;
        }


        /**
         * Установить вес
         *
         * @param float $value Вес
         * @return $this
         */
        public function setValue(float $value): self
        {
            $this->value = $value;
            return $this;
        }

    }
