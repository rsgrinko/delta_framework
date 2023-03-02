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

    namespace Core\Models;

    use Core\CoreException;
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
         * Роль "система"
         */
        const SYSTEM_ROLE_ID = 2;

        /**
         * Роль "доступ в панель администратора"
         */
        const ADMIN_PANEL_ROLE_ID = 3;

        /**
         * Роль "менеджер"
         */
        const MANAGER_ROLE_ID = 4;

        /**
         * Роль "пользователь"
         */
        const USER_ROLE_ID = 5;

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
         * @throws CoreException
         */
        public function getRoles(): array
        {
            $cacheId = md5(__CLASS__ . '::' . __FUNCTION__ . '_' . $this->user->getId());
            if (Cache::check($cacheId)) {
                $userRoles = Cache::get($cacheId);
            } else {
                /** @var  $DB DB */
                $DB = DB::getInstance();

                $res       = $DB->query('SELECT role_id FROM `' . self::USER_ROLES_TABLE . '` WHERE user_id=' . $this->user->getId());
                $userRoles = [];
                if (!empty($res)) {
                    foreach ($res as $row) {
                        $userRoles[] = (int)$row['role_id'];
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
         * @throws CoreException
         */
        public function addRole(int $roleId): ?int
        {
            $cacheId = md5(__CLASS__ . '::' . __FUNCTION__ . '_' . $this->user->getId());
            Cache::delete($cacheId);

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
         * @throws CoreException
         */
        public function deleteRole(int $roleId): ?int
        {
            $cacheId = md5(__CLASS__ . '::' . __FUNCTION__);
            Cache::delete($cacheId);

            /** @var  $DB DB */
            $DB = DB::getInstance();
            return $DB->query('DELETE FROM ' . self::USER_ROLES_TABLE . ' WHERE user_id=' . $this->user->getId() . ' AND role_id=' . $roleId);
        }

        /**
         * Получение полной информации о ролях пользователя
         *
         * @deprecated Реализован новый метод для получения этих данных
         * @return array|null
         * @throws CoreException
         */
        public function getFullRolesOld(): ?array
        {
            return array_map(function ($element) {
                return static::getAllRoles()[$element];
            },
                $this->getRoles());
        }

        /**
         * Получение полной информации о ролях пользователя V2
         *
         * @return array|null
         * @throws CoreException
         */
        public function getFullRoles(): ?array
        {
            /** @var  $DB DB */
            $DB = DB::getInstance();
            return $DB->query(
                'SELECT ' . self::ROLES_TABLE . '.id, ' . self::ROLES_TABLE . '.name, ' . self::ROLES_TABLE . '.description FROM '
                . self::USER_ROLES_TABLE . ' left JOIN ' . self::ROLES_TABLE . ' ON  ' . self::USER_ROLES_TABLE . '.role_id = ' . self::ROLES_TABLE
                . '.id WHERE ' . self::USER_ROLES_TABLE . '.user_id = "' . $this->user->getId() . '"'
            ) ?: [];
        }

        /**
         * Получение всех существующих ролей
         *
         * @return array|null
         * @throws \Core\CoreException
         */
        public static function getAllRoles(): ?array
        {
            $cacheId = md5(__CLASS__ . '::' . __FUNCTION__);
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