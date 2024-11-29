<?php

namespace App\Src\Amo\Contact;

use AmoCRM\AmoAPI;
use App\Src\Amo\Account\AmoAccountService;
use Illuminate\Support\Facades\Http;

class ContactService
{
    public function __construct(private AmoAccountService $accountService)
    {
    }

    public function getContactById(string $id)
    {
        return (new AmoContact())->getContactById($id);
    }

    /**
     * @param string $query
     * @return array<int,AmoContact>
     */
    public function getContactsByQuery(string $query): array
    {
        $result = [];
        $contacts = AmoAPI::getContacts(['query'=>$query]);
        if(!$contacts) {
            throw new \DomainException('contact by Query ' . $query . ' not found');
        }
        foreach ($contacts as $contact) {
            $result[] = new AmoContact($contact);
        }
        return $result;
    }

    public function findAndMergeByPhone(string $contactId)
    {
        $sourceContact = $this->getContactById($contactId);
        $phones = $sourceContact->getPhones();
        $contacts = $this->getMergeIds($phones, $sourceContact);
        if(count($contacts) == 0) {
            return;
        }
        $mergeContacts = [
            $contacts[0],
            $sourceContact,
        ];
        return $this->merge($mergeContacts);
    }

    protected function buildToMerge(array $info)
    {
        $result = [];
        $leads =[];
        foreach ($info['elements'] as $id) {
            $result['id'][] = $id;
        }
        foreach ($info['compare_values'] as $key => $compareValues) {
            if(str_contains($key,'cfv')) {
                $data = null;
                foreach ($compareValues as $item) {
                    if(str_contains($item['values'][0]['value'],'VALUE')) {
                        foreach ($item['values'] as $phone) {
                            $data[] = str_replace('&quot;','"',$phone['value']);
                        }

                    } else {
                        $data = $item['values'][0]['value'];
                        break;
                    }
                }
                $result['result_element']['cfv'][explode('_',$key)[1]] = $data;
            } else {
                $result['result_element'][$key] = $compareValues[array_key_first($compareValues)]['values'][0]['value'];
            }
        }
        if(array_key_exists('LEADS',$info['compare_values'])) {
            foreach ($info['compare_values']['LEADS'] as $lead) {
                foreach ($lead['values'][0]['value'] as $id => $value) {
                    $leads[] = $id;
                }
            }
            $result['result_element']['LEADS'] = $leads;
        }
        $result['result_element']['ID'] = $result['id'][0];
        return $this->toTextEncode($result);

    }

    /**
     * @param AmoContact[] $contacts
     * @return string
     */
    private function contactInfoEncode(array $contacts)
    {
        $toQueryData = [];
        foreach ($contacts as $contact) {
            $toQueryData[] = $contact->getId();
        }
        return $this->toTextEncode(['id'=>$toQueryData]);
    }

    private function toTextEncode(array $data)
    {
//        $str = '';
//        foreach ($Data as $key => $value) {
//            if(gettype($value) == 'array') {
//                foreach ($value as $item) {
//                    $str .= $key."%5B%5D=$item&";
//                }
//                continue;
//            }
//            $str .= $key."[]=$value&";
//        }
//        $str = substr($str,0,-1);
//        return $str;
        $data = http_build_query($data);
        return $data;
    }


    private function getHeaders(array $extraHeaders = [])
    {
        return array_merge([
            'Accept' => '*/*',
            'Accept-Language' => 'en-US,en;q=0.9,ru;q=0.8',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-origin',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            'X-Requested-With' => 'XMLHttpRequest',
            'sec-ch-ua' => '"Not/A)Brand";v="8", "Chromium";v="126", "Google Chrome";v="126"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
        ],$extraHeaders);
    }

    /**
     * @param array $phones
     * @param AmoContact $sourceContact
     * @return AmoContact[]
     */
    public function getMergeIds(array $phones, AmoContact $sourceContact): array
    {
        $entities = [];
        foreach ($phones as $phone) {
            try {
                $contacts = $this->getContactsByQuery($phone->withoutCode);
                usort($contacts, function (AmoContact $a, AmoContact $b) {
                    return $a->getCreatedUnixTime() < $b->getCreatedUnixTime();
                });
                foreach ($contacts as $contact) {
                    foreach ($contact->getPhones() as $p) {
                        if ($p->withoutCode == $phone->withoutCode) {
                            if ($sourceContact->getId() == $contact->getId()) continue;
                            $entities[] = $contact;
                        }
                    }
                }
            } catch (\DomainException) {
            }
        }
        usort($entities, function (AmoContact $a,AmoContact $b) {
            return ($a->getCreatedUnixTime() - $b->getCreatedUnixTime());
        });
        return $entities;
    }

    /**
     * @param array $mergeContacts
     * @return array
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    public function merge(array $mergeContacts): array
    {
        $data = $this->contactInfoEncode($mergeContacts);
        $data = '&' . $data;
        $response = Http::withHeaders($this->getHeaders([
            'Authorization' => 'Bearer ' . $this->accountService->getAccessToken(),
            'Origin' => 'https://' . $this->accountService->getDomain() . '.amocrm.ru',
            'Referer' => 'https://' . $this->accountService->getDomain() . '.amocrm.ru/contacts/list/contacts/?skip_filter=Y',
        ]))->post('https://' . $this->accountService->getDomain() . '.amocrm.ru/ajax/merge/contacts/info/', $data);

        if ($response->status() == 401) {
            $this->accountService->getAccount();
        }

        $response = Http::withHeaders($this->getHeaders([
            'Authorization' => 'Bearer ' . $this->accountService->getAccessToken(),
            'Origin' => 'https://' . $this->accountService->getDomain() . '.amocrm.ru',
            'Referer' => 'https://' . $this->accountService->getDomain() . '.amocrm.ru/contacts/list/contacts/?skip_filter=Y',
        ]))->post('https://' . $this->accountService->getDomain() . '.amocrm.ru/ajax/merge/contacts/info/', $data);


        $info = $response->json()['response'];
        $data = '&' . $this->buildToMerge($info);
        $response = Http::withHeaders($this->getHeaders([
            'Authorization' => 'Bearer ' . $this->accountService->getAccessToken(),
        ]))->post('https://' . $this->accountService->getDomain() . '.amocrm.ru/ajax/merge/contacts/save', $data);
        return [
            'old' => $mergeContacts[0]->getId(), 'source' => $mergeContacts[1]->getId()
        ];
    }
}
