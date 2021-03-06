<?php

namespace sorokinmedia\notificator\services;

use sorokinmedia\notificator\{BaseOutbox, BaseService};
use sorokinmedia\notificator\entities\Outbox\AbstractOutboxInSite;
use sorokinmedia\notificator\interfaces\RecipientInterface;
use Yii;
use yii\db\Exception;

/**
 * Class InSiteService
 * @package common\components\notificator\services
 *
 * @property string $name
 */
class InSiteService extends BaseService
{
    /**
     * @param BaseOutbox $baseOutbox
     * @param string $class
     * @return bool
     * @throws Exception
     */
    public function send(BaseOutbox $baseOutbox, string $class): bool
    {
        $outbox = new $class;
        $recipients = $baseOutbox->recipients instanceof RecipientInterface ? $baseOutbox->recipients->getAccounts($baseOutbox->type_id) : $baseOutbox->recipients;

        if (is_array($recipients) && !array_key_exists($this->getName(), $recipients)) {
            return true;
        }

        // todo: check service/type settings
        if (!$recipients[$this->getName()]) {
            return true;
        }

        /** @var AbstractOutboxInSite $outbox */
        $outbox->to_id = $baseOutbox->to_id;
        $outbox->body = Yii::$app->view->render($this->_getAbsoluteViewPath($baseOutbox), array_merge(
            $baseOutbox->messageData,
            ['outbox' => $outbox]
        ));
        $outbox->type_id = $baseOutbox->type_id;

        if (!$outbox->save()) {
            throw new Exception(Yii::t('app-sm-notificator', 'Ошибка при сохранении в БД'));
        }
        return $outbox->sendOutbox();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'in_site';
    }

    /**
     * @param BaseOutbox $baseOutbox
     * @param string $class
     * @return bool
     */
    public function sendGroup(BaseOutbox $baseOutbox, string $class): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isGroup(): bool
    {
        return false;
    }
}
