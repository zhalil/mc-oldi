<?php

namespace App\Src\Amo\Contact;

use AmoCRM\AmoContact as AmoContactLib;

class AmoContact
{
    private AmoContactLib $amoContact;

    public function __construct(array $data = [])
    {
        $this->amoContact = new AmoContactLib($data);
    }

    public function getCreatedStamp()
    {
        return $this->amoContact->created_at;
    }

    public function getId()
    {
        return $this->amoContact->id;
    }

    public function getCreatedUnixTime()
    {
        return $this->amoContact->created_at;
    }

    public function getContactById(string $id)
    {
        $this->amoContact = (new AmoContactLib())->fillById($id);
        return $this;
    }

    /**
     * @return array<AmoPhone>
     */
    public function getPhones(): array
    {
        $phones = [];
        foreach ($this->amoContact->custom_fields as $field) {
            if(array_key_exists('code',$field) && $field['code'] == 'PHONE') {
                foreach ($field['values'] as $value) {
                    $rule = '~\D+~';
                    $swissNumberStr = preg_replace($rule,'',$value['value']);
                    $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
                    try {
                        $swissNumberProto = $phoneUtil->parse($swissNumberStr, "RU");
                        $withoutCode = $swissNumberProto->getNationalNumber();
                        $withCode = '+' . $swissNumberProto->getCountryCode() . $withoutCode;
                    } catch (\libphonenumber\NumberParseException $e) {
                        $withoutCode = $swissNumberStr;
                        $withCode = $swissNumberStr;
                    }
                    $phones[] = new AmoPhone($withCode,$withoutCode);
                }
            }
        }
        if(count($phones) == 0) {
            throw new \DomainException('phones not found in entity' . $this->amoContact->id);
        }
        return $phones;
    }


}
