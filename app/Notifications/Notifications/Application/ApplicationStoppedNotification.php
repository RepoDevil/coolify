<?php

namespace App\Notifications\Notifications\Application;

use App\Models\Application;
use App\Models\ApplicationPreview;
use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\DiscordChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ApplicationStoppedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public Application $application;

    public string $application_name;
    public string|null $application_url = null;
    public string $project_uuid;
    public string $environment_name;
    public string $fqdn;

    public function __construct(Application $application)
    {
        $this->application = $application;
        $this->application_name = data_get($application, 'name');
        $this->project_uuid = data_get($application, 'environment.project.uuid');
        $this->environment_name = data_get($application, 'environment.name');
        $this->fqdn = data_get($application, 'fqdn');
        if (Str::of($this->fqdn)->explode(',')->count() > 1) {
            $this->fqdn = Str::of($this->fqdn)->explode(',')->first();
        }
        $this->application_url =  base_url() . "/project/{$this->project_uuid}/{$this->environment_name}/application/{$this->application->uuid}";
    }
    public function via(object $notifiable): array
    {
        $channels = [];
        $isEmailEnabled = data_get($notifiable, 'smtp.enabled');
        $isDiscordEnabled = data_get($notifiable, 'discord.enabled');
        $isSubscribedToEmailDeployments = data_get($notifiable, 'smtp_notifications.deployments');
        $isSubscribedToDiscordDeployments = data_get($notifiable, 'discord_notifications.deployments');

        if ($isEmailEnabled && $isSubscribedToEmailDeployments) {
            $channels[] = EmailChannel::class;
        }
        if ($isDiscordEnabled && $isSubscribedToDiscordDeployments) {
            $channels[] = DiscordChannel::class;
        }
        return $channels;
    }
    public function toMail(): MailMessage
    {
        // $mail = new MailMessage();
        // $pull_request_id = data_get($this->preview, 'pull_request_id', 0);
        // $fqdn = $this->fqdn;
        // if ($pull_request_id === 0) {
        //     $mail->subject("✅New version is deployed of {$this->application_name}");
        // } else {
        //     $fqdn = $this->preview->fqdn;
        //     $mail->subject("✅ Pull request #{$pull_request_id} of {$this->application_name} deployed successfully");
        // }
        // $mail->view('emails.application-deployed-successfully', [
        //     'name' => $this->application_name,
        //     'fqdn' => $fqdn,
        //     'deployment_url' => $this->deployment_url,
        //     'pull_request_id' => $pull_request_id,
        // ]);
        // return $mail;
    }

    public function toDiscord(): string
    {
        $message = '⛔ ' . $this->application_name . ' has been stopped.
            
';
        $message .= '[Application URL](' . $this->application_url . ')';
        return $message;
    }
}
