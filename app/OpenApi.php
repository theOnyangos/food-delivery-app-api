<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
	version: '1.0.0',
	title: 'ASL API',
	description: 'API documentation for AMAZING SOULS API'
)]
#[OA\SecurityScheme(
	securityScheme: 'sanctum',
	type: 'http',
	scheme: 'bearer',
	bearerFormat: 'Sanctum',
	description: 'Use a Sanctum personal access token. Swagger will send it as Authorization: Bearer {token}.'
)]
class OpenApi
{
}
