<?php

/*
 * This file is part of the MindbazBundle package.
 *
 * (c) David DELEVOYE <david.delevoye@adeo.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Kozikaza\MindbazBundle\Serializer;

use Kozikaza\MindbazBundle\Model\Subscriber;
use Kozikaza\MindbazBundle\Serializer\SubscriberEncoder;
use mbzSubscriber\Subscriber as MindbazSubscriber;
use mbzSubscriber\SubscriberFieldData;
use PhpSpec\ObjectBehavior;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
class SubscriberEncoderSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(SubscriberEncoder::class);
    }

    function it_supports_encoding()
    {
        $this->supportsEncoding('mindbaz')->shouldBeTrue();
    }

    function it_does_not_support_encoding()
    {
        $this->supportsEncoding('invalid')->shouldBeFalse();
    }

    function it_supports_decoding()
    {
        $this->supportsDecoding('mindbaz')->shouldBeTrue();
    }

    function it_does_not_support_decoding()
    {
        $this->supportsDecoding('invalid')->shouldBeFalse();
    }

    function it_encodes()
    {
        $data = [
            'email'                 => 'foo@example.com',
            'firstSubscriptionDate' => new \DateTime(),
            'lastSubscriptionDate'  => new \DateTime(),
            'unsubscriptionDate'    => new \DateTime(),
            'status'                => Subscriber::STATUS_SUBSCRIBED,
            'civility'              => Subscriber::CIVILITY_MR,
            'lastName'              => 'DOE',
            'firstName'             => 'John',
            'city'                  => 'Lille',
            'zicode'                => '59000',
            'country'               => 'France',
        ];

        $subscriber = $this->encode($data, SubscriberEncoder::FORMAT);
        $subscriber->shouldBeInstanceOf(MindbazSubscriber::class);
        $subscriber->getFld()->shouldBeAnArray();
        $subscriber->getFld()->shouldCount(count($data));
    }

    function it_decodes()
    {
        $data = [
            'email'                 => 'foo@example.com',
            'firstSubscriptionDate' => new \DateTime(),
            'lastSubscriptionDate'  => new \DateTime(),
            'unsubscriptionDate'    => new \DateTime(),
            'status'                => Subscriber::STATUS_SUBSCRIBED,
            'civility'              => Subscriber::CIVILITY_MR,
            'lastName'              => 'DOE',
            'firstName'             => 'John',
            'city'                  => 'Lille',
            'zicode'                => '59000',
            'country'               => 'France',
        ];

        $fld = [];
        foreach ($data as $fieldName => $value) {
            $fld[] = (new SubscriberFieldData(SubscriberEncoder::FIELDS[$fieldName]))->setValue($value);
        }
        $subscriber = new MindbazSubscriber(-1);
        $subscriber->setFld($fld);

        $this->decode($subscriber, SubscriberEncoder::FORMAT)->shouldBeEqualTo($data);
    }

    function getMatchers()
    {
        return [
            'beTrue' => function ($subject) {
                return true === $subject;
            },
            'beFalse' => function ($subject) {
                return false === $subject;
            },
            'beInstanceOf' => function ($subject, $class) {
                return $subject instanceof $class;
            },
            'beAnArray' => function ($subject) {
                return is_array($subject);
            },
            'count' => function ($subject, $count) {
                return $count === count($subject);
            },
        ];
    }
}
