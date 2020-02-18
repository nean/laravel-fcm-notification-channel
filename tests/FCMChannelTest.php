<?php

namespace NotificationChannels\FCM\Test;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use LaravelFCM\Response\DownstreamResponse;
use LaravelFCM\Sender\FCMSender;
use Mockery;
use NotificationChannels\FCM\FCMChannel;
use NotificationChannels\FCM\FCMMessage;
use NotificationChannels\FCM\MessageWasSended;
use PHPUnit\Framework\TestCase;

class FCMChannelTest extends TestCase
{
    public function setUp() : void
    {
        $this->sender = Mockery::mock(FCMSender::class);
        $this->events = Mockery::mock(Dispatcher::class);
        $this->channel = new FCMChannel($this->sender, $this->events);
    }

    public function tearDown() : void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_send_a_notification()
    {
        $notifiable = new TestNotifiableWithArrayOfTokens;
        $notification = new TestNotification;
        $message = $notification->toFCM($notifiable);
        $to = $notifiable->routeNotificationFor('FCM');
        $message->to($to);
        $args = $message->getArgs();
        $this->sender->shouldReceive('sendTo')->with(...$args)->andReturn(Mockery::mock(DownstreamResponse::class));
        $this->events->shouldReceive('dispatch')->with(Mockery::type(MessageWasSended::class));
        $result = $this->channel->send($notifiable, $notification);
        $this->assertInstanceOf(DownstreamResponse::class, $result);
    }

    /** @test */
    public function it_return_null_with_recipient_empty_array()
    {
        $notifiable = new TestNotifiableWithEmptyArrayOfTokens;
        $notification = new TestNotification;
        $message = $notification->toFCM($notifiable);
        $to = $notifiable->routeNotificationFor('FCM');
        $message->to($to);
        $args = $message->getArgs();
        $this->sender->shouldNotReceive('sendTo');
        $this->events->shouldNotReceive('fire');
        $result = $this->channel->send($notifiable, $notification);
        $this->assertNull($result);
    }
}

class TestNotifiableWithArrayOfTokens
{
    use Notifiable;

    public function routeNotificationForFCM()
    {
        return ['test_token'];
    }
}

class TestNotifiableWithEmptyArrayOfTokens
{
    use Notifiable;

    public function routeNotificationForFCM()
    {
        return [];
    }
}

class TestNotifiableWithInvalidRecipient
{
    use Notifiable;

    public function routeNotificationForFCM()
    {
    }
}

class TestNotification extends Notification
{
    public function toFCM($notifiable)
    {
        return new FCMMessage();
    }
}
