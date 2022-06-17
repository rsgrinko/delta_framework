<?php

    namespace Core\Models;

    use Core\Helpers\Cache;
    use Core\Models\User;

    class Roles
    {
        /**
         * Таблица с группами пользователей
         */
        const USER_GROUPS_TABLE = 'user_roles';

        /**
         * Таблица с группами
         */
        const GROUPS_TABLE = 'roles';

        /**
         * Группа "администратор"
         */
        const ADMIN_GROUP_ID = 1;

        /**
         * Группа "пользователь"
         */
        const ADMIN_USER_ID = 2;

        /**
         * @var User $user
         */
        private $user = null;

        public function __construct(User $user)
        {
            $this->user = $user;
        }

        /**
         * Получение групп пользователя
         *
         * @return array
         */
        public function getGroups(): array
        {
            $cacheId = md5('Group_' . $this->user->getId() . '_getGroups');
            if (Cache::check($cacheId)) {
                $userGroups = Cache::get($cacheId);
            } else {
                /** @var  $DB DB */
                $DB = DB::getInstance();

                $res        = $DB->query('SELECT group_id FROM `' . self::USER_GROUPS_TABLE . '` WHERE user_id=' . $this->user->getId());
                $userGroups = [];
                if (!empty($res)) {
                    foreach ($res as $row) {
                        $userGroups[] = $row['group_id'];
                    }
                }
                Cache::set($cacheId, $userGroups);
            }
            return $userGroups;
        }

        /**
         * Добавляет пользователя в указанную группу
         *
         * @param int $groupId Идентификатор группы
         *
         * @return int|null
         */
        public function addToGroup(int $groupId): ?int
        {
            $cacheId = md5('Group_' . $this->user->getId() . '_getGroups');
            Cache::del($cacheId);

            /** @var  $DB DB */
            $DB = DB::getInstance();
            return $DB->addItem(self::USER_GROUPS_TABLE, ['user_id' => $this->user->getId(), 'group_id' => $groupId]);
        }

        /**
         * Убирает пользователя из указанной группы
         *
         * @param int $groupId Идентификатор группы
         *
         * @return int|null
         */
        public function removeFromGroup(int $groupId): ?int
        {
            $cacheId = md5('Group_' . $this->user->getId() . '_getGroups');
            Cache::del($cacheId);

            /** @var  $DB DB */
            $DB = DB::getInstance();
            return $DB->query('DELETE FROM ' . self::USER_GROUPS_TABLE . ' WHERE user_id=' . $this->user->getId() . ' AND group_id=' . $groupId);
        }

        /**
         * Получение полной информации о группах пользователя
         *
         * @return array|null
         */
        public function getFullGroup(): ?array
        {
            return array_map(function ($element) {
                return static::getAllGroups()[$element];
            },
                $this->getGroups());
        }

        /**
         * Получение всех существующих групп
         *
         * @return array|null
         */
        public static function getAllGroups(): ?array
        {
            $cacheId = md5('Group::getAllGroups');
            if (Cache::check($cacheId)) {
                $groups = Cache::get($cacheId);
            } else {
                /** @var  $DB DB */
                $DB = DB::getInstance();
                $res    = $DB->query('SELECT * FROM ' . self::GROUPS_TABLE);
                $groups = [];
                if (!empty($res)) {
                    foreach ($res as $group) {
                        $groups[$group['id']] = $group;
                    }
                }
                Cache::set($cacheId, $groups);
            }
            return $groups;
        }
    }