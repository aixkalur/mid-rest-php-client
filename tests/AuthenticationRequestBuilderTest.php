<?php
/*-
 * #%L
 * Mobile ID sample PHP client
 * %%
 * Copyright (C) 2018 - 2021 SK ID Solutions AS
 * %%
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * #L%
 */
namespace Sk\Mid\Tests;

use Sk\Mid\Language\EST;
use Sk\Mid\Exception\MidDeliveryException;
use Sk\Mid\Exception\MidNotMidClientException;
use Sk\Mid\Exception\MidPhoneNotAvailableException;
use Sk\Mid\Exception\MidUserCancellationException;

use Sk\Mid\Rest\MobileIdConnector;

use Sk\Mid\Tests\Mock\SessionStatusDummy;

use Sk\Mid\Exception\MidSessionTimeoutException;
use Sk\Mid\Exception\MidInternalErrorException;
use Sk\Mid\MobileIdAuthenticationHashToSign;
use Sk\Mid\MobileIdClient;
use Sk\Mid\Rest\MobileIdRestConnector;
use Sk\Mid\Rest\SessionStatusPoller;

use Sk\Mid\Rest\Dao\Request\AuthenticationRequest;


use Sk\Mid\Tests\Mock\MobileIdConnectorSpy;
use Sk\Mid\Rest\Dao\Response\AuthenticationResponse;
use Sk\Mid\Tests\Mock\TestData;
use Sk\Mid\MobileIdSignature;
use Sk\Mid\Rest\Dao\SessionStatus;

use Sk\Mid\Exception\MissingOrInvalidParameterException;

use PHPUnit\Framework\TestCase;

class AuthenticationRequestBuilderTest extends TestCase
{
    /** @var MobileIdConnectorSpy $connector */
    private $connector;

    protected function setUp() : void
    {
        $this->connector = new MobileIdConnectorSpy();
        $this->connector->setAuthenticationResponseToRespond(new AuthenticationResponse( array('sessionId' => TestData::SESSION_ID)));
        $this->connector->setSessionStatusToRespond(self::createDummyAuthenticationSessionStatus());
    }

    /**
     * @test
     */
    public function authenticate_withoutRelyingPartyUUID_shouldThrowException()
    {
        $this->expectException(MissingOrInvalidParameterException::class);

        $mobileAuthenticationHash = MobileIdAuthenticationHashToSign::generateRandomHashOfDefaultType();
        $request = AuthenticationRequest::newBuilder()
            ->withRelyingPartyName(TestData::DEMO_RELYING_PARTY_NAME)
            ->withPhoneNumber(TestData::VALID_PHONE)
            ->withNationalIdentityNumber(TestData::VALID_NAT_IDENTITY)
            ->withHashToSign($mobileAuthenticationHash)
            ->withLanguage(EST::asType())
            ->build();

        $connector = MobileIdRestConnector::newBuilder()
            ->withEndpointUrl(TestData::DEMO_HOST_URL)
            ->withSslPinnedPublicKeys("sha256//k/w7/9MIvdN6O/rE1ON+HjbGx9PRh/zSnNJ61pldpCs=;sha256//some-future-ssl-host-key")
            ->build();

        $connector->initAuthentication($request);
    }

    /**
     * @test
     */
    public function authenticate_withoutRelyingPartyName_shouldThrowException()
    {
        $this->expectException(MissingOrInvalidParameterException::class);

        $mobileAuthenticationHash = MobileIdAuthenticationHashToSign::generateRandomHashOfDefaultType();
        $request = AuthenticationRequest::newBuilder()
            ->withRelyingPartyUUID(TestData::DEMO_RELYING_PARTY_UUID)
            ->withPhoneNumber(TestData::VALID_PHONE)
            ->withNationalIdentityNumber(TestData::VALID_NAT_IDENTITY)
            ->withHashToSign($mobileAuthenticationHash)
            ->withLanguage(EST::asType())
            ->build();

        $connector = MobileIdRestConnector::newBuilder()
            ->withEndpointUrl(TestData::DEMO_HOST_URL)
            ->withSslPinnedPublicKeys(TestData::DEMO_HOST_PUBLIC_KEY_HASH)
            ->build();
        $connector->initAuthentication($request);
    }

    /**
     * @test
     */
    public function authenticate_withoutPhoneNumber_shouldThrowException()
    {
        $this->expectException(MissingOrInvalidParameterException::class);

        $mobileAuthenticationHash = MobileIdAuthenticationHashToSign::generateRandomHashOfDefaultType();
        $request = AuthenticationRequest::newBuilder()
            ->withNationalIdentityNumber(TestData::VALID_NAT_IDENTITY)
            ->withHashToSign($mobileAuthenticationHash)
            ->withLanguage(EST::asType())
            ->build();

        $connector = MobileIdRestConnector::newBuilder()
            ->withEndpointUrl(TestData::DEMO_HOST_URL)
            ->withRelyingPartyUUID(TestData::DEMO_RELYING_PARTY_UUID)
            ->withRelyingPartyName(TestData::DEMO_RELYING_PARTY_NAME)
            ->build();

        $connector->initAuthentication($request);
    }

    /**
     * @test
     */
    public function authenticate_withoutNationalIdentityNumber_shouldThrowException()
    {
        $this->expectException(MissingOrInvalidParameterException::class);

        $mobileAuthenticationHash = MobileIdAuthenticationHashToSign::generateRandomHashOfDefaultType();
        $request = AuthenticationRequest::newBuilder()
            ->withPhoneNumber(TestData::VALID_PHONE)
            ->withHashToSign($mobileAuthenticationHash)
            ->withLanguage(EST::asType())
            ->build();

        $connector = MobileIdRestConnector::newBuilder()
            ->withEndpointUrl(TestData::DEMO_HOST_URL)
            ->withRelyingPartyUUID(TestData::DEMO_RELYING_PARTY_UUID)
            ->withRelyingPartyName(TestData::DEMO_RELYING_PARTY_NAME)
            ->build();

        $connector->initAuthentication($request);
    }

    /**
     * @test
     */
    public function authenticate_withoutHashToSign_shouldThrowException()
    {
        $this->expectException(MissingOrInvalidParameterException::class);

        $request = AuthenticationRequest::newBuilder()
            ->withPhoneNumber(TestData::VALID_PHONE)
            ->withNationalIdentityNumber(TestData::VALID_NAT_IDENTITY)
            ->withLanguage(EST::asType())
            ->build();

        $connector = MobileIdRestConnector::newBuilder()
            ->withEndpointUrl(TestData::DEMO_HOST_URL)
            ->withRelyingPartyUUID(TestData::DEMO_RELYING_PARTY_UUID)
            ->withRelyingPartyName(TestData::DEMO_RELYING_PARTY_NAME)
            ->build();

        $connector->initAuthentication($request);
    }

    /**
     * @test
     */
    public function authenticate_withoutLanguage_shouldThrowException()
    {
        $this->expectException(MissingOrInvalidParameterException::class);

        $mobileAuthenticationHash = MobileIdAuthenticationHashToSign::generateRandomHashOfDefaultType();
        $request = AuthenticationRequest::newBuilder()
            ->withPhoneNumber(TestData::VALID_PHONE)
            ->withNationalIdentityNumber(TestData::VALID_NAT_IDENTITY)
            ->withHashToSign($mobileAuthenticationHash)
            ->build();

        $connector = MobileIdRestConnector::newBuilder()
            ->withEndpointUrl(TestData::DEMO_HOST_URL)
            ->withRelyingPartyUUID(TestData::DEMO_RELYING_PARTY_UUID)
            ->withRelyingPartyName(TestData::DEMO_RELYING_PARTY_NAME)
            ->build();

        $connector->initAuthentication($request);
    }

    /**
     * @test
     */
    public function authenticate_withTimeout_shouldThrowException()
    {
        $this->expectException(MidSessionTimeoutException::class);

        $this->connector->setSessionStatusToRespond(SessionStatusDummy::createTimeoutSessionStatus());
        $this->makeAuthenticationRequest($this->connector);
    }

    /**
     * @test
     */
    public function authenticate_withResponseRetrievingError_shouldThrowException()
    {
        $this->expectException(MidInternalErrorException::class);

        $this->connector->setSessionStatusToRespond(SessionStatusDummy::createResponseRetrievingErrorStatus());
        $this->makeAuthenticationRequest($this->connector);
    }

    /**
     * @test
     */
    public function authenticate_withNotMIDClient_shouldThrowException()
    {
        $this->expectException(MidNotMidClientException::class);

        $this->connector->setSessionStatusToRespond(SessionStatusDummy::createNotMIDClientStatus());
        $this->makeAuthenticationRequest($this->connector);
    }

    /**
 * @test
 */
    public function authenticate_withMSSPTransactionExpired_shouldThrowException()
    {
        $this->expectException(MidSessionTimeoutException::class);

        $this->connector->setSessionStatusToRespond(SessionStatusDummy::createMSSPTransactionExpiredStatus());
        $this->makeAuthenticationRequest($this->connector);
    }

    /**
     * @test
     */
    public function authenticate_withUserCancellation_shouldThrowException()
    {
        $this->expectException(MidUserCancellationException::class);

        $this->connector->setSessionStatusToRespond(SessionStatusDummy::createUserCancellationStatus());
        $this->makeAuthenticationRequest($this->connector);
    }

    /**
     * @test
     */
    public function authenticate_withMIDNotReady_shouldThrowException()
    {
        $this->expectException(MidInternalErrorException::class);

        $this->connector->setSessionStatusToRespond(SessionStatusDummy::createMIDNotReadyStatus());
        $this->makeAuthenticationRequest($this->connector);
    }

    /**
     * @test
     */
    public function authenticate_withSimNotAvailable_shouldThrowException()
    {
        $this->expectException(MidPhoneNotAvailableException::class);

        $this->connector->setSessionStatusToRespond(SessionStatusDummy::createSimNotAvailableStatus());
        $this->makeAuthenticationRequest($this->connector);
    }

    /**
     * @test
     */
    public function authenticate_withDeliveryError_shouldThrowException()
    {
        $this->expectException(MidDeliveryException::class);

        $this->connector->setSessionStatusToRespond(SessionStatusDummy::createDeliveryErrorStatus());
        $this->makeAuthenticationRequest($this->connector);
    }

    /**
     * @test
     */
    public function authenticate_withInvalidCardResponse_shouldThrowException()
    {
        $this->expectException(MidDeliveryException::class);

        $this->connector->setSessionStatusToRespond(SessionStatusDummy::createInvalidCardResponseStatus());
        $this->makeAuthenticationRequest($this->connector);
    }

    /**
     * @test
     */
    public function authenticate_withResultMissingInResponse_shouldThrowException()
    {
        $this->expectException(MidInternalErrorException::class);

        $this->connector->getSessionStatusToRespond()->setResult(null);
        $this->makeAuthenticationRequest($this->connector);
    }

    /**
     * @test
     */
    public function authenticate_withResultBlankInResponse_shouldThrowException()
    {
        $this->expectException(MidInternalErrorException::class);

        $this->connector->getSessionStatusToRespond()->setResult("");
        $this->makeAuthenticationRequest($this->connector);
    }

    /**
     * @test
     */
    public function authenticate_withCertificateBlankInResponse_shouldThrowException()
    {
        $this->expectException(MissingOrInvalidParameterException::class);

        $this->connector->getSessionStatusToRespond()->setCert("");
        $this->makeAuthenticationRequest($this->connector);
    }

    /**
     * @test
     */
    public function authenticate_withCertificateMissingInResponse_shouldThrowException()
    {
        $this->expectException(MissingOrInvalidParameterException::class);

        $this->connector->getSessionStatusToRespond()->setCert(null);
        $this->makeAuthenticationRequest($this->connector);
    }

    private function makeAuthenticationRequest(MobileIdConnector $connector)
    {
        $authenticationHash = MobileIdAuthenticationHashToSign::generateRandomHashOfDefaultType();

        $request = AuthenticationRequest::newBuilder()
            ->withPhoneNumber(TestData::VALID_PHONE)
            ->withNationalIdentityNumber(TestData::VALID_NAT_IDENTITY)
            ->withHashToSign($authenticationHash)
            ->withLanguage(EST::asType())
            ->build();

        $response = $connector->initAuthentication($request);

        $poller = SessionStatusPoller::newBuilder()
                ->withConnector($connector)
                ->build();
        $sessionStatus = $poller->fetchFinalSessionStatus($response->getSessionId());

        $client = MobileIdClient::newBuilder()
            ->withRelyingPartyUUID(TestData::DEMO_RELYING_PARTY_UUID)
            ->withRelyingPartyName(TestData::DEMO_RELYING_PARTY_NAME)
            ->withHostUrl(TestData::DEMO_HOST_URL)
            ->withSslPinnedPublicKeys("sha256//...")
            ->build();

        $client->createMobileIdAuthentication($sessionStatus, $authenticationHash);
    }

    private static function createDummyAuthenticationSessionStatus()
    {
        $signature = MobileIdSignature::newBuilder()
                ->withValueInBase64("c2FtcGxlIHNpZ25hdHVyZQ0K")
                ->withAlgorithmName("sha512WithRSAEncryption")
                ->build();

        $sessionStatus = new SessionStatus();
        $sessionStatus->setState("COMPLETE");
        $sessionStatus->setResult("OK");
        $sessionStatus->setSignature($signature);
        $sessionStatus->setCert(TestData::AUTH_CERTIFICATE_EE);
        return $sessionStatus;
    }
}
