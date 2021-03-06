<?php

namespace sorokinmedia\notificator\entities\NotificationType;

use sorokinmedia\ar_relations\RelationInterface;
use sorokinmedia\notificator\forms\NotificationTypeForm;
use sorokinmedia\notificator\interfaces\NotificationTypeInterface;
use Throwable;
use Yii;
use yii\db\{ActiveQuery, ActiveRecord, Exception, StaleObjectException};

/**
 * This is the model class for table "notification_type".
 *
 * @property integer $id
 * @property string $name
 * @property string $role
 * @property integer $sms
 * @property integer $email
 * @property integer $telegram
 * @property integer $in_site
 */
abstract class AbstractNotificationType extends ActiveRecord implements RelationInterface, NotificationTypeInterface
{
    public $form;

    /**
     * AbstractNotificationType constructor.
     * @param array $config
     * @param NotificationTypeForm|null $form
     */
    public function __construct(array $config = [], NotificationTypeForm $form = null)
    {
        if ($form !== null) {
            $this->form = $form;
        }
        parent::__construct($config);
    }

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return 'notification_type';
    }

    /**
     * получаем дефолтный набор для роли
     * @param array|string $role иногда может приходить массив ролей
     * @return array
     */
    public static function findByRole($role): array
    {
        return static::find()->where(['role' => $role])->all();
    }

    /**
     * @return array
     */
    public static function getTypesArray(): array
    {
        return static::find()
            ->select(['name', 'id'])
            ->indexBy('id')
            ->orderBy(['name' => SORT_ASC])
            ->column();
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [['name', 'role'], 'required'],
            [['sms', 'telegram', 'email', 'in_site'], 'integer'],
            [['name', 'role'], 'string'],
            [['email', 'in_site'], 'default', 'value' => 1],
            [['sms', 'telegram'], 'default', 'value' => 0]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app-sm-notificator', 'ID'),
            'name' => Yii::t('app-sm-notificator', 'Название'),
            'role' => Yii::t('app-sm-notificator', 'Роль'),
            'sms' => Yii::t('app-sm-notificator', 'SMS'),
            'email' => Yii::t('app-sm-notificator', 'E-mail'),
            'telegram' => Yii::t('app-sm-notificator', 'Telegram'),
            'in_site' => Yii::t('app-sm-notificator', 'На сайте')
        ];
    }

    /**
     * @return ActiveQuery
     */
    abstract public function getNotificationConfigs(): ActiveQuery;

    /**
     * @return bool
     * @throws Exception
     * @throws Throwable
     */
    public function insertModel(): bool
    {
        $this->getFromForm();
        if (!$this->insert()) {
            throw new Exception(Yii::t('app-sm-notificator', 'Ошибка при добавлении нового типа уведомления в БД'));
        }
        $this->refresh();
        $this->afterInsertModel();
        return true;
    }

    /**
     * трансфер данных из формы
     */
    public function getFromForm(): void
    {
        if ($this->form !== null) {
            $this->name = $this->form->name;
            $this->role = $this->form->role;
            $this->sms = $this->form->sms;
            $this->telegram = $this->form->telegram;
            $this->email = $this->form->email;
            $this->in_site = $this->form->in_site;
        }
    }

    /**
     * @return bool
     */
    abstract public function afterInsertModel(): bool;

    /**
     * @return bool
     * @throws Exception
     */
    public function updateModel(): bool
    {
        $role_update = false;
        if ($this->role !== $this->form->role) {
            $role_update = true;
        }
        $this->getFromForm();
        if (!$this->save()) {
            throw new Exception(Yii::t('app-sm-notificator', 'Ошибка при сохранении типа уведомления в БД'));
        }
        if ($role_update) { // если сменилась роль
            $this->afterRoleUpdate();
        }
        return true;
    }

    /**
     * @return bool
     */
    abstract public function afterRoleUpdate(): bool;

    /**
     * @return bool
     * @throws Exception
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function deleteModel(): bool
    {
        $this->beforeDeleteModel();
        if (!$this->delete()) {
            throw new Exception(Yii::t('app-sm-notificator', 'Ошибка при удалении типа уведомления из БД'));
        }
        return true;
    }

    /**
     * @return bool
     */
    abstract public function beforeDeleteModel(): bool;
}
