<?php

declare(strict_types=1);

namespace Clients\Api;

use Clients\Contracts\HttpClient;
use DateTimeInterface;
use mysql_xdevapi\Statement;

/**
 * @see https://developers.yclients.com/ru/
 */
final class YclientsApiClient
{
    /**
     * @var string
     * @access private
     */
    private const URL = 'https://api.yclients.com/api/v1';

    /**
     * @var string
     * @access private
     */
    private const METHOD_GET = 'GET';

    /**
     * @var string
     * @access private
     */
    private const METHOD_POST = 'POST';

    /**
     * @var string
     * @access private
     */
    private const METHOD_PUT = 'PUT';

    /**
     * @var string
     * @access private
     */
    private const METHOD_DELETE = 'DELETE';

    /**
     * @param HttpClient $httpClient
     * @param string|null $tokenPartner
     * @access public
     */
    public function __construct(
        private readonly HttpClient $httpClient,
        private ?string             $tokenPartner = null
    )
    {
    }

    /**
     * @param string $tokenPartner
     * @return self
     * @access public
     */
    public function setTokenPartner(string $tokenPartner): self
    {
        $this->tokenPartner = $tokenPartner;

        return $this;
    }

    /**
     * @return string|null
     * @access public
     */
    public function getTokenPartner(): string|null
    {
        return $this->tokenPartner;
    }

    /**
     * @return HttpClient
     * @access public
     */
    public function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }

    /**
     * Получаем токен пользователя по логину-паролю
     *
     * @param string $login
     * @param string $password
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function authorizeUser(string $login, string $password): array
    {
        return $this->request(
            'auth',
            [
                'login' => $login,
                'password' => $password,
            ],
            self::METHOD_POST
        );
    }

    /**
     * Получаем настройки формы бронирования
     *
     * @param int $id
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getBookform(int $id): array
    {
        return $this->request('bookform/' . $id);
    }

    /**
     * Получаем параметры интернационализации
     *
     * @param string $locale - ru-RU, lv-LV, en-US, ee-EE, lt-LT, de-DE, uk-UK
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getI18n(string $locale = 'ru-RU'): array
    {
        return $this->request('i18n/' . $locale);
    }

    /**
     * Получить список услуг доступных для бронирования
     *
     * @param int $companyId
     * @param ?int $staffId - ID сотрудника. Фильтр по идентификатору сотрудника
     * @param ?DateTimeInterface $datetime - дата (в формате iso8601). Фильтр по дате
     *                              бронирования услуги (например '2005-09-09T18:30')
     * @param ?array $serviceIds - ID услуг. Фильтр по списку идентификаторов уже
     *                            выбранных (в рамках одной записи) услуг. Имеет
     *                            смысл если зада фильтр по мастеру и дате.
     * @param ?array $eventIds - ID акций. Фильтр по списку идентификаторов уже выбранных
     *                          (в рамках одной записи) акций. Имеет смысл если зада
     *                          фильтр по мастеру и дате.
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getBookServices(
        int $companyId,
        int $staffId = null,
        DateTimeInterface $datetime = null,
        array $serviceIds = null,
        array $eventIds = null
    ): array
    {
        $parameters = [];

        if ($staffId !== null) {
            $parameters['staff_id'] = $staffId;
        }

        if ($datetime !== null) {
            $parameters['datetime'] = $datetime->format(DateTimeInterface::ISO8601);
        }

        if ($serviceIds !== null) {
            $parameters['service_ids'] = $serviceIds;
        }

        if ($eventIds !== null) {
            $parameters['event_ids'] = $eventIds;
        }

        return $this->request('book_services/' . $companyId, $parameters);
    }

    /**
     * Получить список сотрудников доступных для бронирования
     *
     * @param int $companyId
     * @param ?int $staffId - ID сотрудника. Фильтр по идентификатору сотрудника
     * @param ?DateTimeInterface $datetime - дата (в формате iso8601). Фильтр по дате
     *                              бронирования услуги (например '2005-09-09T18:30')
     * @param ?array $serviceIds - ID услуг. Фильтр по списку идентификаторов уже
     *                            выбранных (в рамках одной записи) услуг. Имеет
     *                            смысл если зада фильтр по мастеру и дате.
     * @param ?array $eventIds - ID акций. Фильтр по списку идентификаторов уже выбранных
     *                          (в рамках одной записи) акций. Имеет смысл если зада
     *                          фильтр по мастеру и дате.
     * @param bool $withoutSeances - Отключает выдачу ближайших свободных сеансов,
     *                               ускоряет получение данных.
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getBookStaff(
        int $companyId,
        int $staffId = null,
        DateTimeInterface $datetime = null,
        array $serviceIds = null,
        array $eventIds = null,
        bool$withoutSeances = false
    ): array
    {
        $parameters = [];

        if ($staffId !== null) {
            $parameters['staff_id'] = $staffId;
        }

        if ($datetime !== null) {
            $parameters['datetime'] = $datetime->format(DateTimeInterface::ISO8601);
        }

        if ($serviceIds !== null) {
            $parameters['service_ids'] = $serviceIds;
        }

        if ($eventIds !== null) {
            $parameters['event_ids'] = $eventIds;
        }

        if ($withoutSeances) {
            $parameters['without_seances'] = true;
        }

        return $this->request('book_staff/' . $companyId, $parameters);
    }

    /**
     * Получить список дат доступных для бронирования
     *
     * @param int $companyId
     * @param ?int $staffId - ID сотрудника. Фильтр по идентификатору сотрудника
     * @param ?array $serviceIds - ID услуг. Фильтр по списку идентификаторов уже
     *                            выбранных (в рамках одной записи) услуг. Имеет
     *                            смысл если зада фильтр по мастеру и дате.
     * @param ?DateTimeInterface $date - Фильтр по месяцу бронирования (например '2015-09-01')
     * @param ?array $eventIds - ID акций. Фильтр по списку идентификаторов уже выбранных
     *                          (в рамках одной записи) акций. Имеет смысл если зада
     *                          фильтр по мастеру и дате.
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getBookDates(
        int $companyId,
        int $staffId = null,
        array $serviceIds = null,
        DateTimeInterface $date = null,
        array $eventIds = null
    ): array
    {
        $parameters = [];

        if ($staffId !== null) {
            $parameters['staff_id'] = $staffId;
        }

        if ($date !== null) {
            $parameters['date'] = $date->format('Y-m-d');
        }

        if ($serviceIds !== null) {
            $parameters['service_ids'] = $serviceIds;
        }

        if ($eventIds !== null) {
            $parameters['event_ids'] = $eventIds;
        }

        return $this->request('book_dates/' . $companyId, $parameters);
    }

    /**
     * Получить список сеансов доступных для бронирования
     *
     * @param int $companyId
     * @param int $staffId - ID сотрудника. Фильтр по идентификатору сотрудника
     * @param DateTimeInterface $date - Фильтр по месяцу бронирования (например '2015-09-01')
     * @param ?array $serviceIds - ID услуг. Фильтр по списку идентификаторов уже
     *                            выбранных (в рамках одной записи) услуг. Имеет
     *                            смысл если зада фильтр по мастеру и дате.
     * @param ?array $eventIds - ID акций. Фильтр по списку идентификаторов уже выбранных
     *                          (в рамках одной записи) акций. Имеет смысл если зада
     *                          фильтр по мастеру и дате.
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getBookTimes(
        int $companyId,
        int $staffId,
        DateTimeInterface $date,
        array $serviceIds = null,
        array $eventIds = null
    ): array
    {
        $parameters = [];

        if ($serviceIds !== null) {
            $parameters['service_ids'] = $serviceIds;
        }

        if ($eventIds !== null) {
            $parameters['event_ids'] = $eventIds;
        }

        return $this->request('book_times/' . $companyId . '/' . $staffId . '/' . $date->format('Y-m-d'), $parameters);
    }

    /**
     * Отправить СМС код подтверждения номера телефона
     *
     * @param int $companyId
     * @param string $phone - Телефон, на который будет отправлен код, вида 79991234567
     * @param ?string $fullName - Имя клиента
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function postBookCode(int $companyId, string $phone, string $fullName = null): array
    {
        $parameters = [
            'phone' => $phone
        ];

        if ($fullName !== null) {
            $parameters['fullname'] = $fullName;
        }

        return $this->request('book_code/' . $companyId, $parameters, self::METHOD_POST);
    }

    /**
     * Проверить параметры записи
     *
     * @param int $companyId
     * @param array $appointments - Массив записей со следующими полями:
     *                              int id - Идентификатор записи
     *                              array services - Массив идентификторов услуг
     *                              array events - Массив идентификторов акций
     *                              int staff_id - Идентификатор специалиста
     *                              string datetime - Дата и время сеанса в формате ISO8601 (2015-09-29T13:00:00+04:00)
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function postBookCheck(int $companyId, array $appointments): array
    {
        // проверим наличие обязательных параметров
        foreach ($appointments as $appointment) {
            if (!isset($appointment['id'], $appointment['staff_id'], $appointment['datetime'])) {
                throw new YclientsException('Запись должна содержать все обязательные поля: id, staff_id, datetime.');
            }
        }

        return $this->request('book_check/' . $companyId, $appointments, self::METHOD_POST);
    }

    /**
     * Создать запись на сеанс
     *
     * @param int $companyId
     * @param array $person - Массив обязательных данных клиента со следующими полями:
     *                        string phone - Телефон клиента вида 79161502239
     *                        string fullname
     *                        string email
     * @param array $appointments - Массив записей со следующими полями:
     *                              int id - Идентификатор записи для обратной связи
     *                              array services - Массив идентификторов услуг
     *                              array events - Массив идентификторов акций
     *                              int staff_id - Идентификатор специалиста
     *                              string datetime - Дата и время сеанса в формате ISO8601 (2015-09-29T13:00:00+04:00)
     * @param ?string $code - Код подтверждения номера телефона
     * @param ?array $notify - Массив используемых нотификацией со следующими ключами:
     *                        string notify_by_sms - За какое кол-во часов напоминанить по смс о записи (0 если не нужно)
     *                        string notify_by_email - За какое кол-во часов напоминанить по email о записи (0 если не нужно)
     * @param ?string $comment - Комментарий к записи
     * @param ?string $apiId - Внешний идентификатор записи
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function postBookRecord(
        int $companyId,
        array $person,
        array $appointments,
        string $code = null,
        array $notify = null,
        string $comment = null,
        string $apiId = null
    ): array
    {
        $parameters = [];

        // проверим наличие обязательных параметров клиента
        if (!isset($person['phone'], $person['fullname'], $person['email'])) {
            throw new YclientsException('Клиент должен содержать все обязательные поля: phone, fullname, email.');
        }

        $parameters = array_merge($parameters, $person);

        if (!count($appointments)) {
            throw new YclientsException('Должна быть хотя бы одна запись.');
        }

        // проверим наличие обязательных параметров записей
        foreach ($appointments as $appointment) {
            if (!isset($appointment['id'], $appointment['staff_id'], $appointment['datetime'])) {
                throw new YclientsException('Запись должна содержать все обязательные поля: id, staff_id, datetime.');
            }
        }

        $parameters['appointments'] = $appointments;

        if ($notify) {
            if (isset($notify['notify_by_sms'])) {
                $parameters['notify_by_sms'] = $notify['notify_by_sms'];
            }
            if (isset($notify['notify_by_email'])) {
                $parameters['notify_by_email'] = $notify['notify_by_email'];
            }
        }

        if ($code !== null) {
            $parameters['code'] = $code;
        }

        if ($comment !== null) {
            $parameters['comment'] = $comment;
        }

        if ($apiId !== null) {
            $parameters['api_id'] = $apiId;
        }

        return $this->request('book_record/' . $companyId, $parameters, self::METHOD_POST);
    }

    /**
     * Авторизоваться по номеру телефона и коду
     *
     * @param string $phone - Телефон, на который будет отправлен код вида 79161005050
     * @param string $code - Код подтверждения номера телефона, высланный по смс
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function postUserAuth(string $phone, string $code): array
    {
        $parameters = [
            'phone' => $phone,
            'code' => $code,
        ];

        return $this->request('user/auth', $parameters, self::METHOD_POST);
    }

    /**
     * Получить записи пользователя
     *
     * @param int $recordId - ID записи, достаточно для удаления записи если пользователь
     *                            авторизован, получить можно из ответа bookRecord()
     * @param ?string $recordHash - HASH записи, обязательно для удаления записи если пользователь
     *                             не авторизован, получить можно из ответа bookRecord()
     * @param ?string $userToken - токен для авторизации пользователя, обязательный, если $recordHash не указан
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getUserRecords(int $recordId, string $recordHash = null, string $userToken = null): array
    {
        if (!$recordHash && !$userToken) {
            trigger_error('getUserRecords() expected Argument 2 or Argument 3 required', E_USER_WARNING);
        }

        return $this->request('user/records/' . $recordId . '/' . $recordHash, [], self::METHOD_GET,
            $userToken ?: true);
    }

    /**
     * Удалить записи пользователя
     *
     * @param int $recordId - ID записи, достаточно для удаления записи если пользователь
     *                            авторизован, получить можно из ответа bookRecord()
     * @param ?string $recordHash - HASH записи, обязательно для удаления записи если пользователь
     *                             не авторизован, получить можно из ответа bookRecord()
     * @param ?string $userToken - Токен для авторизации пользователя, обязательный, если $recordHash не указан
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function deleteUserRecords(int $recordId, string $recordHash = null, string $userToken = null): array
    {
        if (!$recordHash && !$userToken) {
            trigger_error('deleteUserRecords() expected Argument 2 or Argument 3 required', E_USER_WARNING);
        }

        return $this->request('user/records/' . $recordId . '/' . $recordHash, [], self::METHOD_DELETE,
            $userToken ?: true);
    }

    /**
     * Получить список компаний
     *
     * @param ?int $groupId - ID сети компаний
     * @param ?bool $active - Если нужно получить только активные для онлайн-записи компании
     * @param ?bool $moderated - Если нужно получить только прошедшие модерацию компании
     * @param ?bool $forBooking - Если нужно получить поле next_slot по каждой компании
     * @param ?bool $my - Если нужно компании, на управление которыми пользователь имеет права ($userToken тогда обязательно)
     * @param ?string $userToken - Токен для авторизации пользователя, обязательный, если $my указан
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getCompanies(
        int $groupId = null,
        bool $active = null,
        bool $moderated = null,
        bool $forBooking = null,
        bool $my = null,
        string $userToken = null
    ):array
    {
        if ($my && !$userToken) {
            trigger_error('getCompanies() expected Argument 6 if set Argument 5', E_USER_WARNING);
        }

        $parameters = [];

        if ($groupId !== null) {
            $parameters['group_id'] = $groupId;
        }

        if ($active !== null) {
            $parameters['active'] = $active;
        }

        if ($moderated !== null) {
            $parameters['moderated'] = $moderated;
        }

        if ($forBooking !== null) {
            $parameters['forBooking'] = $forBooking;
        }

        if ($my !== null) {
            $parameters['my'] = $my;
        }

        return $this->request('companies', $parameters, self::METHOD_GET, $userToken ?: true);
    }

    /**
     * Создать компанию
     *
     * @param array $fields - Остальные необязательные поля для создания компании
     * @param string $userToken - Токен для авторизации пользователя
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function postCompany(array $fields, string $userToken): array
    {
        if (!isset($fields['title'])) {
            throw new YclientsException('Для создании компании обязательно название компании.');
        }

        return $this->request('companies', $fields, self::METHOD_POST, $userToken);
    }

    /**
     * Получить компанию
     *
     * @param int $id
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getCompany(int $id): array
    {
        return $this->request('company/' . $id);
    }

    /**
     * Изменить компанию
     *
     * @param int $id
     * @param array $fields - Остальные необязательные поля для создания компании
     * @param string $userToken - Токен для авторизации пользователя
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function putCompany(int $id, array $fields, string $userToken): array
    {
        return $this->request('company/' . $id, $fields, self::METHOD_PUT, $userToken);
    }

    /**
     * Удалить компанию
     *
     * @param int $id
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function deleteCompany(int $id): array
    {
        return $this->request('company/' . $id, [], self::METHOD_DELETE);
    }

    /**
     * Получить список категорий услуг
     *
     * @param int $companyId - ID компании
     * @param int $categoryId - ID категории услуг
     * @param ?int $staffId - ID сотрудника (для получения категорий, привязанных к сотруднику)
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getServiceCategories(int $companyId, int $categoryId, int $staffId = null): array
    {
        $parameters = [];

        if ($staffId !== null) {
            $parameters['staff_id'] = $staffId;
        }

        return $this->request('service_categories/' . $companyId . '/' . $categoryId, $parameters);
    }

    /**
     * Создать категорию услуг
     *
     * @param int $companyId - ID компании
     * @param int $categoryId - ID категории услуг
     * @param array $fields - Обязательные поля для категории со следующими полями:
     *                        string title - Название категории
     *                        int api_id - Внешний идентификатор записи
     *                        int weight
     *                        array staff
     * @param string $userToken - Токен для авторизации пользователя
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function postServiceCategories(int $companyId, int $categoryId, array $fields, string $userToken): array
    {
        return $this->request(
            'service_categories/' . $companyId . '/' . $categoryId,
            $fields,
            self::METHOD_POST,
            $userToken
        );
    }

    /**
     * Получить категорию услуг
     *
     * @param int $companyId - ID компании
     * @param int $categoryId - ID категории услуг
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getServiceCategory(int $companyId, int $categoryId): array
    {
        return $this->request('service_category/' . $companyId . '/' . $categoryId);
    }

    /**
     * Изменить категорию услуг
     *
     * @param int $companyId - ID компании
     * @param int $categoryId - ID категории услуг
     * @param array $fields - Обязательные поля для категории со следующими полями:
     *                        string title - Название категории
     *                        int weight
     *                        array staff
     * @param string $userToken - Токен для авторизации пользователя
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function putServiceCategory(int $companyId, int $categoryId, array $fields, string $userToken): array
    {
        return $this->request('service_category/' . $companyId . '/' . $categoryId, $fields, self::METHOD_PUT,
            $userToken);
    }

    /**
     * Удалить категорию услуг
     *
     * @param int $companyId - ID компании
     * @param int $categoryId - ID категории услуг
     * @param string $userToken - Токен для авторизации пользователя
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function deleteServiceCategory(int $companyId, int $categoryId, string $userToken): array
    {
        return $this->request('service_category/' . $companyId . '/' . $categoryId, [], self::METHOD_DELETE,
            $userToken);
    }

    /**
     * Получить список услуг / конкретную услугу
     *
     * @param int $companyId - ID компании
     * @param ?int $serviceId - ID услуги, если нужно работать с конкретной услугой
     * @param ?int $staffId - ID сотрудника, если нужно отфильтровать по сотруднику
     * @param ?int $categoryId - ID категории, если нужно отфильтровать по категории
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getServices(
        int $companyId,
        int $serviceId = null,
        int $staffId = null,
        int $categoryId = null
    ): array
    {
        $parameters = [];

        if ($staffId !== null) {
            $parameters['staff_id'] = $staffId;
        }

        if ($categoryId !== null) {
            $parameters['category_id'] = $categoryId;
        }

        return $this->request('services/' . $companyId . '/' . $serviceId, $parameters);
    }

    /**
     * Создать услугу
     *
     * @param int $companyId - ID компании
     * @param int $serviceId - ID услуги
     * @param int $categoryId - ID категории услуг
     * @param string $title - Название услуги
     * @param string $userToken - Токен для авторизации пользователя
     * @param ?array $fields - Остальные необязательные поля для услуги
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function postServices(
        int $companyId,
        int $serviceId,
        int $categoryId,
        string $title,
        string $userToken,
        array $fields = null
    ): array
    {
        $parameters = [
            'category_id' => $categoryId,
            'title' => $title,
        ];

        $parameters = array_merge($parameters, $fields);

        return $this->request(
            'services/' . $companyId . '/' . $serviceId,
            $parameters,
            self::METHOD_POST, $userToken
        );
    }

    /**
     * Изменить услугу
     *
     * @param int $companyId - ID компании
     * @param int $serviceId - ID услуги
     * @param string $title - Название услуги
     * @param int $categoryId - ID категории услуг
     * @param string $userToken - Токен для авторизации пользователя
     * @param ?array $fields - Остальные необязательные поля для услуги
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function putServices(
        int $companyId,
        int $serviceId,
        int $categoryId,
        string $title,
        string $userToken,
        array $fields = null
    ): array
    {
        $parameters = [
            'category_id' => $categoryId,
            'title' => $title,
        ];

        $parameters = array_merge($parameters, $fields);

        return $this->request(
            'services/' . $companyId . '/' . $serviceId,
            $parameters,
            self::METHOD_PUT, $userToken
        );
    }

    /**
     * Удалить услугу
     *
     * @param int $companyId - ID компании
     * @param int $serviceId - ID услуги
     * @param string $userToken - Токен для авторизации пользователя
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function deleteServices(int $companyId, int $serviceId, string $userToken): array
    {
        return $this->request(
            'services/' . $companyId . '/' . $serviceId,
            [],
            self::METHOD_DELETE, $userToken
        );
    }

    /**
     * Получить список акций / конкретную акцию
     *
     * @param int $companyId - ID компании
     * @param ?int $eventId - ID услуги, если нужно работать с конкретной услугой.
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getEvents(int $companyId, int $eventId = null): array
    {
        return $this->request('events/' . $companyId . '/' . $eventId);
    }

    /**
     * Получить список сотрудников / конкретного сотрудника
     *
     * @param int $companyId - ID компании
     * @param ?int $staffId - ID сотрудника, если нужно работать с конкретным сотрудником
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getStaff(int $companyId, int $staffId = null): array
    {
        return $this->request('staff/' . $companyId . '/' . $staffId);
    }

    /**
     * Добавить нового сотрудника
     *
     * @param int $companyId - ID компании
     * @param int $staffId - ID сотрудника
     * @param string $name - Имя сотрудника
     * @param string $userToken - Токен для авторизации пользователя
     * @param ?array $fields - Остальные необязательные поля для сотрудника
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function postStaff(
        int $companyId,
        int $staffId,
        string $name,
        string $userToken,
        array $fields = null
    ): array
    {
        $parameters = [
            'name' => $name,
        ];

        $parameters = array_merge($parameters, $fields);

        return $this->request('staff/' . $companyId . '/' . $staffId, $parameters, self::METHOD_POST, $userToken);
    }

    /**
     * Изменить сотрудника
     *
     * @param int $companyId - ID компании
     * @param int $staffId - ID сотрудника
     * @param array $fields - Остальные необязательные поля для услуги
     * @param string $userToken - Токен для авторизации пользователя
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function putStaff(int $companyId, int $staffId, array $fields, string $userToken): array
    {
        return $this->request('staff/' . $companyId . '/' . $staffId, $fields, self::METHOD_PUT, $userToken);
    }

    /**
     * Удалить сотрудника
     *
     * @param int $companyId - ID компании
     * @param int $staffId - ID сотрудника
     * @param string $userToken - Токен для авторизации пользователя
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function deleteStaff(int $companyId, int $staffId, string $userToken): array
    {
        return $this->request('staff/' . $companyId . '/' . $staffId, [], self::METHOD_DELETE, $userToken);
    }

    /**
     * Получить список клиентов
     *
     * @param int $companyId - ID компании
     * @param string $userToken - Токен для авторизации пользователя
     * @param ?string $fullname
     * @param ?string $phone
     * @param ?string $email
     * @param ?string $page
     * @param ?string $count
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getClients(
        int $companyId,
        string $userToken,
        string $fullname = null,
        string $phone = null,
        string $email = null,
        string $page = null,
        string $count = null
    ): array
    {
        $parameters = [];

        if ($fullname !== null) {
            $parameters['fullname'] = $fullname;
        }

        if ($phone !== null) {
            $parameters['phone'] = $phone;
        }

        if ($email !== null) {
            $parameters['email'] = $email;
        }

        if ($page !== null) {
            $parameters['page'] = $page;
        }

        if ($count !== null) {
            $parameters['count'] = $count;
        }

        return $this->request('clients/' . $companyId, $parameters, self::METHOD_GET, $userToken);
    }

    /**
     * Добавить клиента
     *
     * @param int $companyId - ID компании
     * @param string $name - Имя клиента
     * @param int $phone - Телефон клиента
     * @param string $userToken - Токен для авторизации пользователя
     * @param ?array $fields - Остальные необязательные поля для клиента
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function postClients(
        int $companyId,
        string $name,
        int $phone,
        string $userToken,
        array $fields = null
    ): array
    {
        $parameters = [
            'name' => $name,
            'phone' => $phone,
        ];

        $parameters = array_merge($parameters, $fields);

        return $this->request('clients/' . $companyId, $parameters, self::METHOD_POST, $userToken);
    }

    /**
     * Получить клиента
     *
     * @param int $companyId - ID компании
     * @param int $id - ID клиента
     * @param string $userToken - Токен для авторизации пользователя
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getClient(int $companyId, int $id, string $userToken): array
    {
        return $this->request('client/' . $companyId . '/' . $id, [], self::METHOD_GET, $userToken);
    }

    /**
     * Редактировать клиента
     *
     * @param int $companyId - ID компании
     * @param int $id - ID клиента
     * @param string $userToken - Токен для авторизации пользователя
     * @param array $fields
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function putClient(int $companyId, int $id, string $userToken, array $fields): array
    {
        return $this->request('client/' . $companyId . '/' . $id, $fields, self::METHOD_PUT, $userToken);
    }

    /**
     * Удалить клиента
     *
     * @param int $companyId - ID компании
     * @param int $id - ID клиента
     * @param string $userToken - Токен для авторизации пользователя
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function deleteClient(int $companyId, int $id, string $userToken): array
    {
        return $this->request('client/' . $companyId . '/' . $id, [], self::METHOD_DELETE, $userToken);
    }

    /**
     * Получить список записей
     *
     * @param int $companyId - ID компании
     * @param string $userToken - Токен для авторизации пользователя
     * @param ?int $page
     * @param ?int $count
     * @param ?int $staffId
     * @param ?int $clientId
     * @param ?DateTimeInterface $startDate
     * @param ?DateTimeInterface $endDate
     * @param ?DateTimeInterface $cStartDate
     * @param ?DateTimeInterface $cEndDate
     * @param ?DateTimeInterface $changedAfter
     * @param ?DateTimeInterface $changedBefore
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getRecords(
        int $companyId,
        string $userToken,
        int $page = null,
        int $count = null,
        int $staffId = null,
        int $clientId = null,
        DateTimeInterface $startDate = null,
        DateTimeInterface $endDate = null,
        DateTimeInterface $cStartDate = null,
        DateTimeInterface $cEndDate = null,
        DateTimeInterface $changedAfter = null,
        DateTimeInterface $changedBefore = null
    ): array
    {
        $parameters = [];

        if ($page !== null) {
            $parameters['page'] = $page;
        }

        if ($count !== null) {
            $parameters['count'] = $count;
        }

        if ($staffId !== null) {
            $parameters['staff_id'] = $staffId;
        }

        if ($clientId !== null) {
            $parameters['client_id'] = $clientId;
        }

        if ($startDate !== null) {
            $parameters['start_date'] = $startDate->format('Y-m-d');
        }

        if ($endDate !== null) {
            $parameters['end_date'] = $endDate->format('Y-m-d');
        }

        if ($cStartDate !== null) {
            $parameters['c_start_date'] = $cStartDate->format('Y-m-d');
        }

        if ($cEndDate !== null) {
            $parameters['c_end_date'] = $cEndDate->format('Y-m-d');
        }

        if ($changedAfter !== null) {
            $parameters['changed_after'] = $changedAfter->format(DateTimeInterface::ISO8601);
        }

        if ($changedBefore !== null) {
            $parameters['changed_before'] = $changedBefore->format(DateTimeInterface::ISO8601);
        }

        return $this->request('records/' . $companyId, $parameters, self::METHOD_GET, $userToken);
    }

    /**
     * Создать новую запись
     *
     * @param int $companyId - ID компании
     * @param string $userToken - Токен для авторизации пользователя
     * @param int $staffId
     * @param array $services
     * @param array $client
     * @param DateTimeInterface $datetime
     * @param int $seanceLength
     * @param bool $saveIfBusy
     * @param bool $sendSms
     * @param ?string $comment
     * @param ?int $smsRemainHours
     * @param ?int $emailRemainHours
     * @param ?int $apiId
     * @param ?int $attendance
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function postRecords(
        int $companyId,
        string $userToken,
        int $staffId,
        array $services,
        array $client,
        DateTimeInterface $datetime,
        int $seanceLength,
        bool $saveIfBusy,
        bool $sendSms,
        string $comment = null,
        int $smsRemainHours = null,
        int $emailRemainHours = null,
        int $apiId = null,
        int $attendance = null
    ): array
    {
        $parameters = [];

        if ($staffId !== null) {
            $parameters['staff_id'] = $staffId;
        }

        if ($services !== null) {
            $parameters['services'] = $services;
        }

        if ($client !== null) {
            $parameters['client'] = $client;
        }

        if ($datetime !== null) {
            $parameters['datetime'] = $datetime->format(DateTimeInterface::ISO8601);
        }

        if ($seanceLength !== null) {
            $parameters['seance_length'] = $seanceLength;
        }

        if ($saveIfBusy !== null) {
            $parameters['save_if_busy'] = $saveIfBusy;
        }

        if ($sendSms !== null) {
            $parameters['send_sms'] = $sendSms;
        }

        if ($comment !== null) {
            $parameters['comment'] = $comment;
        }

        if ($smsRemainHours !== null) {
            $parameters['sms_remain_hours'] = $smsRemainHours;
        }

        if ($emailRemainHours !== null) {
            $parameters['email_remain_hours'] = $emailRemainHours;
        }

        if ($apiId !== null) {
            $parameters['api_id'] = $apiId;
        }

        if ($attendance !== null) {
            $parameters['attendance'] = $attendance;
        }

        return $this->request('records/' . $companyId, $parameters, self::METHOD_POST, $userToken);
    }

    /**
     * Получить запись
     *
     * @param int $companyId - ID компании
     * @param int $recordId
     * @param string $userToken - Токен для авторизации пользователя
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getRecord(int $companyId, int $recordId, string $userToken): array
    {
        return $this->request('record/' . $companyId . '/' . $recordId, [], self::METHOD_GET, $userToken);
    }

    /**
     * Изменить запись
     *
     * @param int $companyId - ID компании
     * @param int $recordId
     * @param string $userToken - Токен для авторизации пользователя
     * @param array $fields
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function putRecord(int $companyId, int $recordId, string $userToken, array $fields): array
    {
        return $this->request(
            'record/' . $companyId . '/' . $recordId,
            $fields,
            self::METHOD_PUT, $userToken
        );
    }

    /**
     * Удалить запись
     *
     * @param int $companyId - ID компании
     * @param int $recordId
     * @param string $userToken - Токен для авторизации пользователя
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function deleteRecord(int $companyId, int $recordId, string $userToken): array
    {
        return $this->request('record/' . $companyId . '/' . $recordId, [], self::METHOD_DELETE, $userToken);
    }

    /**
     * Изменить расписание работы сотрудника
     *
     * @param int $companyId - ID компании
     * @param int $staffId
     * @param string $userToken - Токен для авторизации пользователя
     * @param array $fields
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function putSchedule(int $companyId, int $staffId, string $userToken, array $fields): array
    {
        return $this->request(
            'schedule/' . $companyId . '/' . $staffId,
            $fields,
            self::METHOD_PUT, $userToken)
            ;
    }

    /**
     * Получить список дат для журнала
     *
     * @param int $companyId - ID компании
     * @param DateTimeInterface $date
     * @param int $staffId
     * @param string $userToken - Токен для авторизации пользователя
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getTimetableDates(int $companyId, DateTimeInterface $date, int $staffId, string $userToken): array
    {
        $parameters = [];

        $parameters['staff_id'] = $staffId;

        return $this->request(
            'timetable/dates/' . $companyId . '/' . $date->format('Y-m-d'),
            $parameters,
            self::METHOD_GET, $userToken
        );
    }

    /**
     * Получить список сеансов для журнала
     *
     * @param int $companyId - ID компании
     * @param DateTimeInterface $date
     * @param int $staffId
     * @param string $userToken - Токен для авторизации пользователя
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getTimetableSeances(int $companyId, DateTimeInterface $date, int $staffId, string $userToken): array
    {
        return $this->request(
            'timetable/seances/' . $companyId . '/' . $staffId . '/' . $date->format('Y-m-d'),
            [],
            self::METHOD_GET, $userToken
        );
    }

    /**
     * Получить комментарии
     *
     * @param int $companyId - ID компании
     * @param string $userToken - Токен для авторизации пользователя
     * @param ?DateTimeInterface $startDate
     * @param ?DateTimeInterface $endDate
     * @param ?int $staffId
     * @param ?int $rating
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getComments(
        int $companyId,
        string $userToken,
        DateTimeInterface $startDate = null,
        DateTimeInterface $endDate = null,
        int $staffId = null,
        int $rating = null
    ): array
    {
        $parameters = [];

        if ($startDate !== null) {
            $parameters['start_date'] = $startDate->format('Y-m-d');
        }

        if ($endDate !== null) {
            $parameters['end_date'] = $endDate->format('Y-m-d');
        }

        if ($staffId !== null) {
            $parameters['staff_id'] = $staffId;
        }

        if ($rating !== null) {
            $parameters['rating'] = $rating;
        }

        return $this->request('comments/' . $companyId, $parameters, self::METHOD_GET, $userToken);
    }

    /**
     * Получить пользователей компании
     *
     * @param int $companyId - ID компании
     * @param string $userToken - Токен для авторизации пользователя
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getCompanyUsers(int $companyId, string $userToken): array
    {
        return $this->request('company_users/' . $companyId, [], self::METHOD_GET, $userToken);
    }

    /**
     * Получить кассы компании
     *
     * @param int $companyId - ID компании
     * @param string $userToken - Токен для авторизации пользователя
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getAccounts(int $companyId, string $userToken): array
    {
        return $this->request('accounts/' . $companyId, [], self::METHOD_GET, $userToken);
    }

    /**
     * Отправить SMS
     *
     * @param int $companyId - ID компании
     * @param string $userToken - Токен для авторизации пользователя
     * @param int[] $clientIds - ID клиентов
     * @param string $text - Тест сообщения
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function sendSMS(int $companyId, string $userToken, array $clientIds, string $text): array
    {
        $parameters = [];
        $parameters['client_ids'] = $clientIds;
        $parameters['text'] = $text;

        return $this->request('sms/clients/by_id/' . $companyId, $parameters, self::METHOD_POST, $userToken);
    }

    /**
     * Получить склады компании
     *
     * @param int $companyId - ID компании
     * @param string $userToken - Токен для авторизации пользователя
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getStorages(int $companyId, string $userToken): array
    {
        return $this->request('storages/' . $companyId, [], self::METHOD_GET, $userToken);
    }

    /**
     * Получить настройки уведомлений о событиях
     *
     * @param int $companyId - ID компании
     * @param string $userToken - Токен для авторизации пользователя
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function getHooks(int $companyId, string $userToken): array
    {
        return $this->request('hooks_settings/' . $companyId, [], self::METHOD_GET, $userToken);
    }

    /**
     * Изменить настройки уведомлений о событиях
     *
     * @param int $companyId - ID компании
     * @param array $fields
     * @param string $userToken - Токен для авторизации пользователя
     * @return array
     * @access public
     * @throws YclientsException
     */
    public function postHooks(int $companyId, array $fields, string $userToken): array
    {
        if (!isset($fields['url'])) {
            throw new YclientsException('Не передан обязательный параметр url');
        }
        if (!isset($fields['active'])) {
            throw new YclientsException('Не передан обязательный параметр active');
        }
        return $this->request('hooks_settings/' . $companyId, $fields, self::METHOD_POST, $userToken);
    }

    /**
     * Подготовка запроса
     *
     * @param string $url
     * @param array $parameters
     * @param string $method
     * @param bool|string $auth - если true, то авторизация партнёрская
     *                            если string, то авторизация пользовательская
     * @return array
     * @access protected
     * @throws YclientsException
     */
    protected function request(
        string $url,
        array $parameters = [],
        string $method = 'GET',
        bool|string $auth = true
    ): array
    {
        $headers = [
            'Content-type' => 'application/json',
            'Accept' => 'application/vnd.api.v2+json',
        ];

        if ($auth) {
            if (!$this->tokenPartner) {
                throw new YclientsException('Не указан токен партнёра');
            }

            $headers['Authorization'] = 'Bearer ' . $this->tokenPartner . (is_string($auth) ? ', User ' . $auth : '');
        }

        return $this->requestCurl($url, $parameters, $method, $headers);
    }

    /**
     * Выполнение непосредственно запроса с помощью curl
     *
     * @param string $url
     * @param array $parameters
     * @param string $method
     * @param array $headers
     * @param int $timeout
     * @return array
     * @access protected
     * @throws YclientsException
     */
    private function requestCurl(
        string $url,
        array $parameters = [],
        string $method = 'GET',
        array $headers = [],
        int $timeout = 30
    ): array
    {
        $request = $this->getHttpClient()->setHeaders($headers)->setTimeout($timeout);

        if (count($parameters)) {
            if ($method === self::METHOD_GET) {
                $url .= '?' . http_build_query($parameters);
            } else {
                $request->setBody($parameters, true);
            }
        }

        $url = self::URL . '/' . $url;

        $response = match ($method) {
            self::METHOD_GET => $request->get($url),
            self::METHOD_POST => $request->post($url),
            self::METHOD_PUT => $request->put($url),
            self::METHOD_DELETE => $request->delete($url),
        };

        $errNumber = $this->getHttpClient()->getErrNumber();
        $errMessage = $this->getHttpClient()->getErrMessage();

        if (!empty($errNumber)) throw new YclientsException($errMessage, $errNumber);

        return json_decode($response, true);
    }
}
