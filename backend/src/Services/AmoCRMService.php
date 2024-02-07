<?php

namespace App\Services;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\Leads\LeadsCollection;
use AmoCRM\Collections\TasksCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\CompanyModel;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\Models\NoteType\ServiceMessageNote;
use AmoCRM\Models\TaskModel;
use AmoCRM\Models\UserModel;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

class AmoCRMService
{
    public function __construct(private AmoCRMApiClient $client)
    {
        $this->client->setAccessToken($this->getAccessToken());
    }

    public function getLeadById(
        string|int $id,
        array $options = []
    ): LeadModel|null {
        try {
            return $this->client->leads()->getOne($id, $options);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getUserById(int|string $id): UserModel
    {
        $userService = $this->client->users();
        return $userService->getOne($id);
    }

    public function createNoteAfterAddLead(array $data): void
    {
        $idLead = $data["id"];
        $nameLead = $data["name"];
        $cratedAt = (int)$data["created_at"];
        $date = date("d/m/Y H:i:s", $cratedAt);
        $responsibleUserId = $data["responsible_user_id"];
        $user = $this->getUserById($responsibleUserId);
        try {
            $text = "Название ".$nameLead.", Ответственный ".$user->getName()
                .", Дата создания ".$date;
            $serviceMessageNote = new ServiceMessageNote();
            $serviceMessageNote->setEntityId($idLead)
                ->setText($text)
                ->setService("Создание сделки")
                ->setCreatedBy($responsibleUserId);
            $leadNotesService = $this->client->notes(
                EntityTypesInterface::LEADS
            );
            $notesCollection = $leadNotesService->addOne($serviceMessageNote);
        } catch (\Exception $e) {
        }
    }

    public function createNoteAfterUpdateLead(array $data): void
    {
        $idLead = $data["id"];
        $nameLead = $data["name"];
        $cratedAt = (int)$data["updated_at"];
        $date = date("d/m/Y H:i:s", $cratedAt);
        $responsibleUserId = $data["responsible_user_id"];
        $user = $this->getUserById($responsibleUserId);
        try {
            $text = "Название ".$nameLead.", Ответственный ".$user->getName()
                .", Дата изменения ".$date;
            $serviceMessageNote = new ServiceMessageNote();
            $serviceMessageNote->setEntityId($idLead)
                ->setText($text)
                ->setService("Изменение сделки")
                ->setCreatedBy($responsibleUserId);
            $leadNotesService = $this->client->notes(
                EntityTypesInterface::LEADS
            );
            $notesCollection = $leadNotesService->addOne($serviceMessageNote);
        } catch (\Exception $e) {
        }
    }

    public function createNoteAfterAddContact(array $data): void
    {
        $idLead = array_keys($data["linked_leads_id"])[0];
        $nameContact = $data["name"];
        $cratedAt = (int)$data["created_at"];
        $date = date("d/m/Y H:i:s", $cratedAt);
        $responsibleUserId = $data["responsible_user_id"];
        $user = $this->getUserById($responsibleUserId);
        $lead = $this->getLeadById($idLead);
        try {
            $text = "Название ".$lead->getName()."/".$nameContact
                .", Ответственный "
                .$user->getName()
                .", Дата и время добавленя ".$date;
            $serviceMessageNote = new ServiceMessageNote();
            $serviceMessageNote->setEntityId($idLead)
                ->setText($text)
                ->setService("Добавление карточки")
                ->setCreatedBy($responsibleUserId);
            $leadNotesService = $this->client->notes(
                EntityTypesInterface::LEADS
            );
            $notesCollection = $leadNotesService->addOne($serviceMessageNote);
        } catch (\Exception $e) {
        }
    }

    public function createNoteAfterUpdateContact(array $data): void
    {
        $idLead = array_keys($data["linked_leads_id"])[0];
        $nameContact = $data["name"];
        $updated_at = (int)$data["updated_at"];
        $date = date("d/m/Y H:i:s", $updated_at);
        $responsibleUserId = $data["responsible_user_id"];
        $companyName = $data["company_name"] != null ? ',компания '
            .$data["company_name"].", " : " ";
        $fields = $data["custom_fields"];
        $fieldsValues = [];
        if ($fields != null) {
            foreach ($fields as $key => $value) {
                $valueData = $value["values"][0]["value"];
                $fieldsValues[] = $value["name"]." ".$valueData;
            }
        }

        try {
            $text = "Название карточки ".$nameContact.$companyName;
            foreach ($fieldsValues as $key => $value) {
                $text .= $value.",";
            }
            $text .= $date;
            $serviceMessageNote = new ServiceMessageNote();
            $serviceMessageNote->setEntityId($idLead)
                ->setText($text)
                ->setService("Обновление карточки")
                ->setCreatedBy($responsibleUserId);
            $leadNotesService = $this->client->notes(
                EntityTypesInterface::LEADS
            );
            $notesCollection = $leadNotesService->addOne($serviceMessageNote);
        } catch (\Exception $e) {
        }
    }

    public function getAccessToken(): AccessToken|null
    {
        $subdomain = $_ENV['SUBDOMAIN'];
        $baseDomain = $subdomain.'.amocrm.ru';
        $this->client->getOAuthClient()->setBaseDomain($baseDomain);
        $accessToken = $this->GetTokenFromFile();
        if ($accessToken != null) {
            if ($accessToken->hasExpired()) {
                $accessToken = $this->client->getOAuthClient()
                    ->getAccessTokenByRefreshToken($accessToken);
                $this->saveAccessTokenInFile($accessToken);
            }
            return $accessToken;
        }
        $link = 'https://'.$baseDomain.'/oauth2/access_token';
        $clientId = $_ENV['CLIENT_ID'];
        $clientSecret = $_ENV['CLIENT_SECRET'];
        $redirectUri = $_ENV['CLIENT_REDIRECT_URI'];
        $code = $_ENV['CODE'];
        $data = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ];
        try {
            $clientHttp = new \GuzzleHttp\Client();
            $response = $clientHttp->post($link, [
                'json' => $data,
                'timeout' => 5
            ]);
            $json = (array)json_decode($response->getBody());
            $accessToken = new AccessToken($json);
            $this->saveAccessTokenInFile($accessToken);
            return $accessToken;
        } catch (\Exception $exception) {
            echo $exception;
        }
        return null;
    }

    public function GetTokenFromFile(): AccessToken|null
    {
        $pathToFile = dirname(__DIR__)."/../token.json";
        if (!file_exists($pathToFile)) {
            return null;
        }
        $accessToken = json_decode(file_get_contents($pathToFile), true);
        return new AccessToken([
            'access_token' => $accessToken['accessToken'],
            'refresh_token' => $accessToken['refreshToken'],
            'expires' => $accessToken['expires']
        ]);
    }

    public function saveAccessTokenInFile(
        AccessToken $accessToken
    ): void {
        $pathToFile = dirname(__DIR__)."/../token.json";
        $data = [
            'accessToken' => $accessToken->getToken(),
            'expires' => $accessToken->getExpires(),
            'refreshToken' => $accessToken->getRefreshToken(),
        ];

        file_put_contents($pathToFile, json_encode($data));
    }
}