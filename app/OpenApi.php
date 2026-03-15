<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
	version: '1.0.0',
	title: 'ASL API',
	description: 'API documentation for ASL API'
)]
#[OA\SecurityScheme(
	securityScheme: 'sanctum',
	type: 'apiKey',
	in: 'header',
	name: 'Authorization',
	description: 'Enter token as: Bearer {token}'
)]
class OpenApi
{
}
