<?php

    namespace Core\Models;

    use Core\Helpers\Cache;
    use Core\Models\User;

    class Roles
    {
        /**
         * Таблица с ролями пользователей
         */
        const USER_ROLES_TABLE = 'user_roles';

        /**
         * Таблица с ролями
         */
        const ROLES_TABLE = 'roles';

        /**
         * Роль "администратор"
         */
        const ADMIN_ROLE_ID = 1;

        /**
         * Роль "пользователь"
         */
        const USER_ROLE_ID = 2;

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
         */
        public function getRoles(): array
        {
            $cacheId = md5('Roles_' . $this->user->getId() . '_getRoles');
            if (Cache::check($cacheId)) {
                $userRoles = Cache::get($cacheId);
            } else {
                /** @var  $DB DB */
                $DB = DB::getInstance();

                $res       = $DB->query('SELECT role_id FROM `' . self::USER_ROLES_TABLE . '` WHERE user_id=' . $this->user->getId());
                $userRoles = [];
                if (!empty($res)) {
                    foreach ($res as $row) {
                        $userRoles[] = $row['role_id'];
                    }
                }
                Cache::set($cacheId, $userRoles);
            }
            return $userRoles;
        }

        /**
         * Добавляет пользователю указанную роль
         *
         * @param int $roleId ID роли
         *
         * @return int|null
         */
        public function addRole(int $roleId): ?int
        {
            $cacheId = md5('Roles_' . $this->user->getId() . '_getRoles');
            Cache::del($cacheId);

            /** @var  $DB DB */
            $DB = DB::getInstance();
            return $DB->addItem(self::USER_ROLES_TABLE, ['user_id' => $this->user->getId(), 'role_id' => $roleId]);
        }

        /**
         * Убирает пользователю указанную роль
         *
         * @param int $roleId ID роли
         *
         * @return int|null
         */
        public function removeRole(int $roleId): ?int
        {
            $cacheId = md5('Roles_' . $this->user->getId() . '_getRoles');
            Cache::del($cacheId);

            /** @var  $DB DB */
            $DB = DB::getInstance();
            return $DB->query('DELETE FROM ' . self::USER_ROLES_TABLE . ' WHERE user_id=' . $this->user->getId() . ' AND role_id=' . $roleId);
        }

        /**
         * Получение полной информации о ролях пользователя
         *
         * @return array|null
         */
        public function getFullRoles(): ?array
        {
            return array_map(function ($element) {
                return static::getAllRoles()[$element];
            },
                $this->getRoles());
        }

        /**
         * Получение всех существующих ролей
         *
         * @return array|null
         */
        public static function getAllRoles(): ?array
        {
            $cacheId = md5('Roles::getAllRoles');
            if (Cache::check($cacheId)) {
                $roles = Cache::get($cacheId);
            } else {
                /** @var  $DB DB */
                $DB    = DB::getInstance();
                $res   = $DB->query('SELECT * FROM ' . self::ROLES_TABLE);
                $roles = [];
                if (!empty($res)) {
                    foreach ($res as $role) {
                        $roles[$role['id']] = $role;
                    }
                }
                Cache::set($cacheId, $roles);
            }
            return $roles;
        }
    }