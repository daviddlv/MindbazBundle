<?php

/*
 * This file is part of the MindbazBundle package.
 *
 * (c) David DELEVOYE <david.delevoye@adeo.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\MindbazBundle\Manager;

use mbzOneshot\OneshotWebService;
use mbzOneshot\Send;
use mbzOneshot\SendResponse;
use mbzSubscriber\ArrayOfInt;
use mbzSubscriber\ArrayOfString;
use mbzSubscriber\ArrayOfSubscriber;
use mbzSubscriber\GetSubscribersByEmail;
use mbzSubscriber\GetSubscribersByEmailResponse;
use mbzSubscriber\InsertSubscriber;
use mbzSubscriber\InsertSubscriberResponse;
use mbzSubscriber\Subscriber as MindbazSubscriber;
use mbzSubscriber\SubscriberWebService;
use mbzSubscriber\Unsubscribe;
use mbzSubscriber\UnsubscribeResponse;
use MindbazBundle\Manager\SubscriberManager;
use MindbazBundle\Model\Subscriber;
use MindbazBundle\Serializer\SubscriberEncoder;
use PhpSpec\ObjectBehavior;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
class SubscriberManagerSpec extends ObjectBehavior
{
    public function let(SubscriberWebService $subscriberWebService, OneshotWebService $oneshotWebService, SerializerInterface $serializer, LoggerInterface $logger)
    {
        $serializer->implement(DenormalizerInterface::class);
        $this->beConstructedWith($subscriberWebService, $oneshotWebService, $serializer, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SubscriberManager::class);
    }

    public function it_creates_and_inserts_a_subscriber_from_data(SerializerInterface $serializer, SubscriberWebService $subscriberWebService, InsertSubscriberResponse $response, LoggerInterface $logger, Subscriber $subscriber, MindbazSubscriber $mindbazSubscriber)
    {
        $serializer->denormalize([
            'email'     => 'foo@example.com',
            'firstName' => 'John',
            'lastName'  => 'DOE',
        ], Subscriber::class)->willReturn($subscriber)->shouldBeCalledTimes(1);
        $serializer->serialize($subscriber, SubscriberEncoder::FORMAT)->willReturn($mindbazSubscriber)->shouldBeCalledTimes(1);
        $subscriberWebService->InsertSubscriber(new InsertSubscriber($mindbazSubscriber->getWrappedObject(), true))->willReturn($response)->shouldBeCalledTimes(1);
        $response->getInsertSubscriberResult()->willReturn(123)->shouldBeCalledTimes(1);
        $subscriber->setId(123)->shouldBeCalledTimes(1);
        $subscriber->getId()->willReturn(123)->shouldBeCalledTimes(1);
        $logger->info('New subscriber inserted in Mindbaz', ['id' => 123])->shouldBeCalledTimes(1);

        $this->create([
            'email'     => 'foo@example.com',
            'firstName' => 'John',
            'lastName'  => 'DOE',
        ]);
    }

    public function it_successfully_unsubscribes_a_subscriber(SubscriberWebService $subscriberWebService, UnsubscribeResponse $response, LoggerInterface $logger, Subscriber $subscriber, MindbazSubscriber $mindbazSubscriber)
    {
        $subscriber->getId()->willReturn(123)->shouldBeCalledTimes(2);
        $subscriberWebService->Unsubscribe(new Unsubscribe(123, null, null))->willReturn($response)->shouldBeCalledTimes(1);
        $response->getUnsubscribeResult()->willReturn(true)->shouldBeCalledTimes(1);
        $logger->info('Subscriber successfully unsubscribed', ['id' => 123])->shouldBeCalledTimes(1);
        $logger->error('An error occurred while unsubscribing subscriber', ['id' => 123, 'response' => false])->shouldNotBeCalled();

        $this->unsubscribe($subscriber);
    }

    public function it_doesnt_unsubscribes_a_subscriber(SubscriberWebService $subscriberWebService, UnsubscribeResponse $response, LoggerInterface $logger, Subscriber $subscriber)
    {
        $subscriber->getId()->willReturn(123)->shouldBeCalledTimes(2);
        $subscriberWebService->Unsubscribe(new Unsubscribe(123, null, null))->willReturn($response)->shouldBeCalledTimes(1);
        $response->getUnsubscribeResult()->willReturn(false)->shouldBeCalledTimes(2);
        $logger->info('Subscriber successfully unsubscribed', ['id' => 123])->shouldNotBeCalled();
        $logger->error('An error occurred while unsubscribing subscriber', ['id' => 123, 'response' => false])->shouldBeCalledTimes(1);

        $this->unsubscribe($subscriber);
    }

    public function it_finds_no_subscribers_by_email(SubscriberWebService $subscriberWebService, GetSubscribersByEmailResponse $response)
    {
        $subscriberWebService->GetSubscribersByEmail(new GetSubscribersByEmail(
            (new ArrayOfString())->setString(['foo@example.com']),
            (new ArrayOfInt())->setInt([0, 1])
        ))->willReturn($response)->shouldBeCalledTimes(1);
        $response->getGetSubscribersByEmailResult()->shouldBeCalledTimes(1);

        $this->findByEmail(['foo@example.com'])->shouldBeEqualTo([]);
    }

    public function it_finds_subscribers_by_email(SerializerInterface $serializer, SubscriberWebService $subscriberWebService, GetSubscribersByEmailResponse $response, ArrayOfSubscriber $subscribers, Subscriber $subscriber, MindbazSubscriber $mindbazSubscriber)
    {
        $subscriberWebService->GetSubscribersByEmail(new GetSubscribersByEmail(
            (new ArrayOfString())->setString(['foo@example.com']),
            (new ArrayOfInt())->setInt([0, 1])
        ))->willReturn($response)->shouldBeCalledTimes(1);
        $response->getGetSubscribersByEmailResult()->willReturn($subscribers)->shouldBeCalledTimes(1);
        $subscribers->getSubscriber()->willReturn([$mindbazSubscriber])->shouldBeCalledTimes(1);
        $serializer->deserialize($mindbazSubscriber, Subscriber::class, SubscriberEncoder::FORMAT)->willReturn($subscriber)->shouldBeCalledTimes(1);
        ;

        $this->findByEmail(['foo@example.com'])->shouldBeEqualTo([$subscriber]);
    }

    public function it_does_not_find_one_subscriber_by_email(SubscriberWebService $subscriberWebService, GetSubscribersByEmailResponse $response)
    {
        $subscriberWebService->GetSubscribersByEmail(new GetSubscribersByEmail(
            (new ArrayOfString())->setString(['foo@example.com']),
            (new ArrayOfInt())->setInt([0, 1])
        ))->willReturn($response)->shouldBeCalledTimes(1);
        $response->getGetSubscribersByEmailResult()->shouldBeCalledTimes(1);

        $this->findOneByEmail('foo@example.com')->shouldBeNull();
    }

    public function it_finds_one_subscriber_by_email(SerializerInterface $serializer, SubscriberWebService $subscriberWebService, GetSubscribersByEmailResponse $response, ArrayOfSubscriber $subscribers, Subscriber $subscriber, MindbazSubscriber $mindbazSubscriber)
    {
        $subscriberWebService->GetSubscribersByEmail(new GetSubscribersByEmail(
            (new ArrayOfString())->setString(['foo@example.com']),
            (new ArrayOfInt())->setInt([0, 1])
        ))->willReturn($response)->shouldBeCalledTimes(1);
        $response->getGetSubscribersByEmailResult()->willReturn($subscribers)->shouldBeCalledTimes(1);
        $subscribers->getSubscriber()->willReturn([$mindbazSubscriber])->shouldBeCalledTimes(1);
        $serializer->deserialize($mindbazSubscriber, Subscriber::class, SubscriberEncoder::FORMAT)->willReturn($subscriber)->shouldBeCalledTimes(1);
        ;

        $this->findOneByEmail('foo@example.com')->shouldBeEqualTo($subscriber);
    }

    public function it_successfully_sends_a_message(OneshotWebService $oneshotWebService, SendResponse $response, Subscriber $subscriber, \Swift_Mime_Message $message, \Swift_Mime_MimeEntity $child, LoggerInterface $logger)
    {
        $subscriber->getId()->willReturn(456)->shouldBeCalledTimes(2);
        $message->getChildren()->willReturn([$child])->shouldBeCalledTimes(1);
        $child->getContentType()->willReturn('text/plain')->shouldBeCalledTimes(1);
        $child->getBody()->willReturn('Foo')->shouldBeCalledTimes(1);
        $message->getContentType()->willReturn('text/html')->shouldBeCalledTimes(2);
        $message->getBody()->willReturn('<p>Foo</p>')->shouldBeCalledTimes(1);
        $message->getSender()->willReturn('noreply@example.com')->shouldBeCalledTimes(1);
        $message->getSubject()->willReturn('Bar')->shouldBeCalledTimes(1);
        $oneshotWebService->Send(new Send(
            123,
            456,
            '<p>Foo</p>',
            'Foo',
            'noreply@example.com',
            'Bar'
        ))->willReturn($response)->shouldBeCalledTimes(1);
        $response->getSendResult()->willReturn(SubscriberManager::MINDBAZ_SEND_RESPONSE_OK)->shouldBeCalledTimes(1);
        $logger->info('Message successfully sent to subscriber', ['id' => 456])->shouldBeCalledTimes(1);

        $this->send(123, $subscriber, $message);
    }

    public function it_unsuccessfully_sends_a_message(OneshotWebService $oneshotWebService, SendResponse $response, Subscriber $subscriber, \Swift_Mime_Message $message, LoggerInterface $logger)
    {
        $subscriber->getId()->willReturn(456)->shouldBeCalledTimes(2);
        $message->getChildren()->willReturn([])->shouldBeCalledTimes(1);
        $message->getContentType()->willReturn('text/html')->shouldBeCalledTimes(2);
        $message->getBody()->willReturn('<p>Foo</p>')->shouldBeCalledTimes(1);
        $message->getSender()->willReturn('noreply@example.com')->shouldBeCalledTimes(1);
        $message->getSubject()->willReturn('Bar')->shouldBeCalledTimes(1);
        $oneshotWebService->Send(new Send(
            123,
            456,
            '<p>Foo</p>',
            null,
            'noreply@example.com',
            'Bar'
        ))->willReturn($response)->shouldBeCalledTimes(1);
        $response->getSendResult()->willReturn(SubscriberManager::MINDBAZ_SEND_RESPONSE_NOK)->shouldBeCalledTimes(2);
        $logger->error('An error occurred while sending the message to subscriber', ['id' => 456, 'response' => SubscriberManager::MINDBAZ_SEND_RESPONSE_NOK])->shouldBeCalledTimes(1);

        $this->send(123, $subscriber, $message);
    }

    public function getMatchers()
    {
        return [
            'beNull' => function ($subject) {
                return null === $subject;
            },
        ];
    }
}
