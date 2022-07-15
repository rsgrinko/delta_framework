<?php

    namespace Core\Models;

    use Core\Helpers\Cache;

    class UserMeta
    {
        /**
         * Таблица с мета полями
         */
        const TABLE = 'user_meta';

        /**
         * @var User $user
         */
        private $user = null;

        public function __construct(User $user)
        {
            $this->user = $user;
        }

        /**
         * Получение ролей пользователя
         *
         * @return array
         * @throws \Core\CoreException
         */
        public function getAllMeta(): array
        {
            $cacheId = md5('Meta_' . $this->user->getId() . '_getAllMeta');
            if (Cache::check($cacheId)) {
                $userMeta = Cache::get($cacheId);
            } else {
                /** @var  $DB DB */
                $DB = DB::getInstance();

                $res      = $DB->query('SELECT * FROM `' . self::TABLE . '` WHERE user_id=' . $this->user->getId());
                $userMeta = [];
                if (!empty($res)) {
                    foreach ($res as $meta) {
                        $userMeta[] = [
                            'id'    => $meta['id'],
                            'name'  => $meta['name'],
                            'value' => $meta['value'],
                        ];
                    }
                }
                Cache::set($cacheId, $userMeta);
            }
            return $userMeta;
        }

        /**
         * Получает указанные мета данные пользователя
         *
         * @param string $name Имя мета
         *
         * @return array|null
         * @throws \Core\CoreException
         */
        public function getMetaByName(string $name): ?array
        {
            $cacheId = md5('Meta_' . $this->user->getId() . '_getMetaByName');
            if (Cache::check($cacheId)) {
                $userMeta = Cache::get($cacheId);
            } else {
                /** @var  $DB DB */
                $DB  = DB::getInstance();
                $res = $DB->query('SELECT * FROM `' . self::TABLE . '` WHERE user_id=' . $this->user->getId() . ' AND name="' . $name . '"');
                if (!empty($res)) {
                    $userMeta = [];
                    foreach ($res as $meta) {
                        $userMeta[] = [
                            'id'    => $meta['id'],
                            'name'  => $meta['name'],
                            'value' => $meta['value'],
                        ];
                    }
                    Cache::set($cacheId, $userMeta);
                }
            }
            return $userMeta;
        }

        /**
         * Добавляет пользователю указанные мета данные
         *
         * @param string      $name  Имя мета
         * @param string|null $value Значение мета
         * @param string      $type
         *
         * @return int|null
         * @throws \Core\CoreException
         */
        public function addMeta(string $name, ?string $value = null): ?int
        {
            $cacheId = md5('Meta_' . $this->user->getId() . '_getAllMeta');
            Cache::delete($cacheId);

            /** @var  $DB DB */
            $DB = DB::getInstance();
            return $DB->addItem(self::TABLE, ['user_id' => $this->user->getId(), 'name' => $name, 'value' => $value]);
        }


        /**
         * Убирает пользователю указанные мета данные
         *
         * @param int $id ID Мета
         *
         * @return void
         * @throws \Core\CoreException
         */
        public function deleteMeta(int $id): void
        {
            $cacheId = md5('Roles_' . $this->user->getId() . '_getRoles');
            Cache::delete($cacheId);

            /** @var  $DB DB */
            $DB = DB::getInstance();
            $DB->query('DELETE FROM ' . self::TABLE . ' WHERE user_id=' . $this->user->getId() . ' AND id="' . $id . '"');
        }
    }