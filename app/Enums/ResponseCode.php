<?php declare(strict_types=1);

namespace App\Enums;

/**
 * @method static static SUCCESS()
 * @method static static GENERAL_ERROR()
 * @method static static VALIDATION_ERROR()
 * @method static static MODEL_NOT_FOUND()
 * @method static static UN_AUTHENTICATED()
 * @method static static METHOD_NOT_ALLOWED()
 * @method static static METHOD_NOT_FOUND()
 * @method static static UN_AUTHORIZED()
 */
final class ResponseCode extends Enum
{
    const SUCCESS = 'S00';

    const SUCCESS_CREATE_PAYMENT = 'S01';

    const GENERAL_ERROR = 'E001';

    const VALIDATION_ERROR = 'E002';

    const MODEL_NOT_FOUND = 'E003';

    const UN_AUTHENTICATED = 'E004';

    const METHOD_NOT_ALLOWED = 'E005';

    const METHOD_NOT_FOUND = 'E006';

    const UN_AUTHORIZED = 'E007';

    const NOT_VERIFY = 'E008';

    const INCOMPLETE_PROFILE = 'E009';

    const CUSTOMER_NOT_ACTIVE = 'E010';

    const CUSTOMER_MUST_HAVE_CAR = 'E011';

    const INVALID_TOKEN = 'E012';

    const MAX_VERIFICATION_RETRIES = 'E013';

    const IMAGE_NOT_FOUND = 'E014';

    const PAYMENT_ERROR = 'E015';

    const INVALID_VERSION = 'E016';

    const PHARMACY_NOT_FOUND = 'E017';

    const ITEM_Exists_ONLY_IN_ITEM_CARDS = 'E018';

    const ITEM_NOT_Exists_IN_ITEM_CARDS = 'E019';

    const NEGATIVE_QuANTITIES_IN_EXISTING_ITEMS = 'E020';

    const USER_NOT_HAVE_ACTIVE_SUBSCRIPTION = 'E021' ;

    const FEATURE_NOT_EXISTS_IN_THIS_SUBSCRIPTION = 'E022';
}
