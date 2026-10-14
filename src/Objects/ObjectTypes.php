<?php

namespace SendKuy\Objects;

final class ObjectTypes
{
    /** @var array<string, class-string<\SendKuy\SendKuyObject>> */
    public const MAP = [
        'account' => Account::class,
        'account_usage' => AccountUsage::class,
        'campaign' => Campaign::class,
        'campaign_dispatch' => CampaignDispatch::class,
        'contact' => Contact::class,
        'contact_attribute' => ContactAttribute::class,
        'conversation' => Conversation::class,
        'conversation_message' => ConversationMessage::class,
        'gateway' => Gateway::class,
        'group' => Group::class,
        'group_membership' => GroupMembership::class,
        'job' => Job::class,
        'message' => Message::class,
        'template' => Template::class,
        'verification' => Verification::class,
        'webhook' => Webhook::class,
        'webhook_delivery' => WebhookDelivery::class,
        'workflow' => Workflow::class,
        'workflow_execution' => WorkflowExecution::class,
        'workflow_trigger' => WorkflowTrigger::class,
    ];
}
