<?php

namespace App\Src\Amo\Lead;

use AmoCRM\AmoAPI;
use AmoCRM\AmoLead;
use App\Src\Amo\Account\AmoAccountService;
use App\Src\Amo\Lead\Data\FindResponse;
use App\Src\Amo\Lead\Data\Status;
use App\Src\Amo\Lead\Query\FindLeadsByText;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LeadService
{
    public function __construct(private AmoAccountService $accountService)
    {
    }

    /**
     * @param FindLeadsByText $query
     * @return FindResponse[]
     * @throws ConnectionException
     */
    public function find(FindLeadsByText $query): array
    {
        $domain = $this->accountService->getDomain();
        [$response, $data] = $this->getRequest($domain, $query);

        if(!isset($data['response']['items']['leads']['current_pipeline'])) {
            throw new HttpResponseException(response("leads in pipeline $query->pipelineId with query $query->text not found"));
        }
        $result = [];
        $leads = $data['response']['items']['leads']['current_pipeline'];



        foreach ($leads as $lead) {
            $statusId = 0;
            if($lead['status']['name'] === 'Успешно реализовано') {
                $statusId = 142;
            }

            if($lead['status']['name'] === 'Закрыто и не реализовано') {
                $statusId = 143;
            }

            $result[] = new FindResponse(
                $lead['id'],
                $lead['name'],
                $lead['pipeline_id'],
                $lead['pipeline_name'],
                $lead['contact_name'],
                new Status(
                    $lead['status']['name'],
                    $lead['status']['color'],
                    $statusId
                )
            );
        }
        return $result;
    }


    public function getLead(int|string $leadId)
    {
        $lead = new AmoLead();
        $lead->fillById($leadId);
        return $lead;
    }

    /**
     * @param int $sourceLeadId
     * @param FindResponse[] $leads
     * @return array
     * @throws ConnectionException
     */
    public function merge(int $sourceLeadId, array $leads, int $pipelineId)
    {
        $leadsIds = [];
        foreach ($leads as $lead) {
            if($lead->status->statusId !== 143 && $lead->status->statusId !== 142) {
                $leadsIds[] = $lead->id;
            }
        }

        if(count($leadsIds) === 0) {
            return;
        }

        $mergeTargetLead = $this->getFreshLead($leadsIds);

        $ids = [
            'id'=>[
                $mergeTargetLead->id,
                $sourceLeadId,
            ]
        ];

        $data = '&' . http_build_query($ids);

        $response = Http::withHeaders($this->getHeaders([
            'Authorization' => 'Bearer ' . $this->accountService->getAccessToken(),
            'Origin' => 'https://'.$this->accountService->getDomain().'.amocrm.ru',
            'Referer' => 'https://'.$this->accountService->getDomain().'.amocrm.ru/contacts/list/contacts/?skip_filter=Y',
        ]))->post('https://'.$this->accountService->getDomain().'.amocrm.ru/ajax/merge/leads/info/',$data);
        $build = $this->buildToMerge($response->json()['response']);

        $info = $response->json()['response'];
        $data = '&' .$this->buildToMerge($info);
        $response = Http::withHeaders($this->getHeaders([
            'Authorization' => 'Bearer ' . $this->accountService->getAccessToken(),
        ]))->post('https://'.$this->accountService->getDomain().'.amocrm.ru/ajax/merge/leads/save',$data);

        return ['response'=>$response,];
    }


    protected function buildToMerge(array $info)
    {
        $result = [];
        $contacts =[];
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
        if(array_key_exists('CONTACTS',$info['compare_values'])) {
            foreach ($info['compare_values']['CONTACTS'] as $contact) {
                foreach ($contact['values'][0]['value'] as $id => $value) {
                    $contacts[] = $id;
                }
            }
            $result['result_element']['CONTACTS'] = $contacts;
        }
        $result['result_element']['ID'] = $result['id'][0];
        return http_build_query($result);
    }





    private function getHeaders(array $extraHeaders = []) : array
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
     * @param string $domain
     * @param FindLeadsByText $query
     * @return array
     * @throws ConnectionException
     */
    private function getRequest(string $domain, FindLeadsByText $query): array
    {
        $url = "https://$domain.amocrm.ru/ajax/v1/elements/list?term=$query->text&pipeline_id=$query->pipelineId";
        $origin = "https://$domain.amocrm.ru";
        $referer = "https://$domain.amocrm.ru/leads/list/pipeline/$query->pipelineId/";
        $response = Http::withHeaders($this->getHeaders([
            'Authorization' => 'Bearer ' . $this->accountService->getAccessToken(),
            'Origin' => $origin,
            'Referer' => $referer,
        ]))->get($url);
        if ($response->status() == 401) {
            $this->accountService->getAccount();
        }
        $response = Http::withHeaders($this->getHeaders([
            'Authorization' => 'Bearer ' . $this->accountService->getAccessToken(),
            'Origin' => $origin,
            'Referer' => $referer,
        ]))->get($url);
        $data = $response->json();
        return array($response, $data);
    }

    /**
     * @param array $leadsIds
     * @return AmoLead
     */
    public function getFreshLead(array $leadsIds): AmoLead
    {
        $leads = [];
        foreach ($leadsIds as $id) {
            $leads[] = $this->getLead($id);
        }
        usort($leads, function (AmoLead $a, AmoLead $b) {
            return $a->created_at > $b->created_at;
        });
        return $leads[array_key_last($leads)];
    }


    private function postRequest(string $domain,string $path, int $pipelineId,$data): array
    {
        $path = Str::start($path,'/');
        $url = "https://$domain.amocrm.ru$path";
        $origin = "https://$domain.amocrm.ru";
        $referer = "https://$domain.amocrm.ru/leads/list/pipeline/$pipelineId/";
        $response = Http::withHeaders($this->getHeaders([
            'Authorization' => 'Bearer ' . $this->accountService->getAccessToken(),
            'Origin' => $origin,
            'Referer' => $referer,
        ]))->post($url);
        if ($response->status() == 401) {
            $this->accountService->getAccount();
        }
        $response = Http::withHeaders($this->getHeaders([
            'Authorization' => 'Bearer ' . $this->accountService->getAccessToken(),
            'Origin' => $origin,
            'Referer' => $referer,
        ]))->post($url);
        $data = $response->json();
        return array($response, $data);
    }
}
