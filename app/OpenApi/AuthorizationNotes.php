<?php

namespace App\OpenApi;

/**
 * Reusable OpenAPI 403 description strings for permission-based access.
 */
final class AuthorizationNotes
{
    public const FORBIDDEN_MANAGE_UPLOADS = 'Forbidden — requires manage uploads permission or Super Admin, Admin, or Partner role';

    public const FORBIDDEN_MANAGE_ROLES = 'Forbidden — requires manage roles permission';

    public const FORBIDDEN_MANAGE_USERS = 'Forbidden — requires manage users permission or Super Admin role';

    public const FORBIDDEN_NOTIFICATIONS = 'Forbidden — requires view notifications or manage notifications permission or Super Admin, Admin, or Partner role';

    public const FORBIDDEN_MANAGE_CHAT = 'Forbidden — requires manage chat permission';

    public const FORBIDDEN_CHAT_CONVERSATIONS = 'Forbidden — requires use live chat or manage chat permission or Super Admin, Admin, Partner, or Customer role';

    public const FORBIDDEN_MANAGE_DELIVERY_ZONES = 'Forbidden — requires manage delivery zones permission';

    public const FORBIDDEN_MANAGE_DELIVERY_ADDRESSES = 'Forbidden — requires manage delivery addresses permission or Super Admin, Admin, Partner, or Customer role';

    public const FORBIDDEN_MANAGE_MEALS = 'Forbidden — requires manage meals permission or Super Admin, Admin, or Partner role';

    public const FORBIDDEN_MANAGE_MEAL_CATEGORIES = 'Forbidden — requires manage meal categories permission';

    public const FORBIDDEN_SUPER_ADMIN_ONLY = 'Forbidden — requires Super Admin role';

    public const FORBIDDEN_USE_AI_CHAT = 'Forbidden — requires use ai chat permission or Super Admin, Admin, or Partner role';

    public const FORBIDDEN_MANAGE_AI_AGENT = 'Forbidden — requires manage ai agent permission';

    public const FORBIDDEN_MANAGE_NEWSLETTER = 'Forbidden — requires manage newsletter permission (Super Admin only)';

    public const FORBIDDEN_MANAGE_REVIEW_CATEGORIES = 'Forbidden — requires manage review categories permission';

    public const FORBIDDEN_MANAGE_REVIEW_TOPICS = 'Forbidden — requires manage review topics permission';

    public const FORBIDDEN_MANAGE_REVIEWS = 'Forbidden — requires manage reviews permission';

    public const FORBIDDEN_MANAGE_CONTENT = 'Forbidden — requires manage content permission or Super Admin or Admin role';

    public const FORBIDDEN_ADMIN_MEAL_INGREDIENTS = 'Forbidden — requires Super Admin or Admin role';
}
