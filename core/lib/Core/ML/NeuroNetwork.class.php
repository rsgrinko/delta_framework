<?php

    namespace Core\ML;

    class NeuroNetwork
    {

        /** @var string $storageFile Файл-хранилище весов */
        private $storageFile;

        /** @var array $arValues Файл-хранилище весов */
        private $arValues;

        /**
         * @var array $layers Массив массивов нейронов
         *                    [
         *                    [], // Входной слой
         *                    [], // Скрытый слой
         *                    [], // Выходной слой
         *                    ];
         */
        private array $layers;

        /** @var array Массив синапсов */
        private array $synapses;

        /**
         * Конструктор
         */
        public function __construct(?string $storageFile = null)
        {
            // Хранилище весов
            if ($storageFile === null) {
                $this->storageFile = $_SERVER['DOCUMENT_ROOT'] . '/neuroSynaps.json';
            } else {
                $this->storageFile = $storageFile;
            }

            // Слои нейросети
            $this->layers = [
                [new Neuron(1), new Neuron(2)],
                [new Neuron(3), new Neuron(4), new Neuron(5), new Neuron(6)],
                [new Neuron(7)],
            ];


            /** Строим связи нейронов */
            $this->generateSynapses();
            /*$this->synaps = [
                 new Synaps($this->layers[0][0], $this->layers[1][0]),
                 new Synaps($this->layers[0][0], $this->layers[1][1]),
                 new Synaps($this->layers[0][0], $this->layers[1][2]),
                 new Synaps($this->layers[0][0], $this->layers[1][3]),
                 new Synaps($this->layers[0][1], $this->layers[1][0]),
                 new Synaps($this->layers[0][1], $this->layers[1][1]),
                 new Synaps($this->layers[0][1], $this->layers[1][2]),
                 new Synaps($this->layers[0][1], $this->layers[1][3]),
                 new Synaps($this->layers[1][0], $this->layers[2][0]),
                 new Synaps($this->layers[1][0], $this->layers[2][1]),
                 new Synaps($this->layers[1][1], $this->layers[2][0]),
                 new Synaps($this->layers[1][1], $this->layers[2][1]),
                 new Synaps($this->layers[1][2], $this->layers[2][0]),
                 new Synaps($this->layers[1][2], $this->layers[2][1]),
                 new Synaps($this->layers[1][3], $this->layers[2][0]),
                 new Synaps($this->layers[1][3], $this->layers[2][1]),
             ];*/

            // Получение сохраненных данных по весам
            $this->getSynapsValue();
        }

        /**
         * Генерируем связи между нейронами
         */
        private function generateSynapses(): void
        {
            $synapsId    = 1;
            $layersCount = count($this->layers);

            // Бежим по слоям нейронов (последний нам не нужен) и строим связи
            for ($i = 0; $i < $layersCount - 1; $i++) {
                $neuronCount = count($this->layers[$i + 1]);
                for ($j = 0; $j < $neuronCount; $j++) {
                    $neuronInCount = count($this->layers[$i]);
                    for ($k = 0; $k < $neuronInCount; $k++) {
                        $this->synapses[] = new Synaps(
                            $synapsId, $this->layers[$i][$k], $this->layers[$i + 1][$j], $this->arValues[$synapsId] ?? null
                        );
                        $synapsId++;
                    }
                }
            }
        }

        /**
         * Сохранить значения весов синапсов в файл
         *
         * @param bool $forceUpdate Принудительно перезаписать
         */
        private function saveSynapsValue(bool $forceUpdate= false): void
        {
            $data = [];
            foreach ($this->synapses as $synapse) {
                /** @var Synaps $synapse */
                $data[$synapse->getId()] = $synapse->getValue();
            }

            if ($forceUpdate || !file_exists($this->storageFile)) {
                echo 'SAVED FILE' . PHP_EOL;
                file_put_contents($this->storageFile, json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        }

        /**
         * Получить значения весов синапсов из файла
         *
         * @return array Сохраненный раннее массив весов
         */
        private function getSynapsValue(): array
        {
            if (!file_exists($this->storageFile)) {
                return [];
            }

            // Получаем массив весов
            $synapsValues = json_decode(file_get_contents($this->storageFile), true);

            // Устанавливаем веса для синапсов
            foreach ($this->synapses as $synapse) {
                /** @var Synaps $synapse */
                $synapse->setValue($synapsValues[$synapse->getId()]);
            }

            $this->arValues = $synapsValues;

            // Возвращаем веса
            return $synapsValues;
        }

        /**
         * Мутация веса
         *
         * @param float|null $shift Величина сдвига для мутации
         *
         * @throws \Exception
         */
        public function mutate(?float $shift = 0.1): self
        {
            foreach ($this->synapses as $synapse) {
                /** @var Synaps $synapse */
                $tempValue = random_int((int)(($synapse->getValue() - $shift) * 100), (int)(($synapse->getValue() + $shift) * 100)) / 100;

                // Ограничиваемся диапазоном 0..1
                if ($tempValue < 0) {
                    $tempValue = 0;
                }
                if ($tempValue > 1) {
                    $tempValue = 1;
                }

                $synapse->setValue($tempValue);
            }

            $this->saveSynapsValue(true);
            return $this;
        }

        /**
         * Запуск обработки
         */
        public function run(array $arValue): array
        {
            foreach ($arValue as $key => $value) {
                $this->layers[0][$key]->setValue($value);
            }
            foreach ($this->synapses as $synapse) {
                $this->step($synapse);
            }

            $result = [];
            // Отдаем значения нейронов последнего слоя как результат
            foreach ($this->layers[2] as $neuron) {
                $result[] = $neuron->getValue();
            }
            $this->saveSynapsValue();
            return $result;
            //return [$this->layers[2][0]->getValue(), $this->layers[2][1]->getValue()];
        }


        /**
         * Шаг преобразования значений между нейронами
         */
        public function step(Synaps $synaps): void
        {
            /*
             // Умножение значения на коэфициент
             $synaps->getOutNeuron()->setValue(
                $synaps->getInNeuron()->getValue() * $synaps->getValue()
            );*/

            // Гиперболический тангенс
            // https://otus.ru/journal/kak-sozdat-nejroset/
            // Функция активации
            $synaps->getOutNeuron()->setValue(
                tanh($synaps->getInNeuron()->getValue() * $synaps->getValue())
            );
        }
    }