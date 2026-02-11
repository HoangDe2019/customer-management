<?php

namespace App\OpenApi;

/**
 * OpenAPI 3.0 specification for Customer Management API.
 *
 * @OA\OpenApi(
 *     openapi="3.0.0",
 *     info=@OA\Info(
 *         title="Customer Management API",
 *         version="5.0.0",
 *         description="JWT-authenticated API: agents, transactions, daily advances, EOD settlement, statistics (completed only), MoMo QR, CCCD scan."
 *     ),
 *     servers={@OA\Server(url="/api", description="API Base URL")},
 *     components=@OA\Components(
 *         securitySchemes={
 *             @OA\SecurityScheme(
 *                 securityScheme="bearerAuth",
 *                 type="http",
 *                 scheme="bearer",
 *                 bearerFormat="JWT",
 *                 description="JWT from POST /auth/login or /auth/register. Header: Authorization: Bearer {token}"
 *             )
 *         }
 *     ),
 *     security={{"bearerAuth":{}}},
 *     tags={
 *         @OA\Tag(name="Auth", description="Login, register, logout, refresh, me"),
 *         @OA\Tag(name="Agents", description="Agent CRUD and user-agents"),
 *         @OA\Tag(name="Transactions", description="Transactions and export"),
 *         @OA\Tag(name="Settlements", description="Daily advances and EOD"),
 *         @OA\Tag(name="Statistics", description="Stats by agent/period"),
 *         @OA\Tag(name="MoMo", description="QR generation and webhook"),
 *         @OA\Tag(name="Config", description="App config (fees, status, amounts)")
 *     }
 * )
 */
class BaseSpec
{
}
