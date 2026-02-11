<?php

namespace App\Models;

use App\Models\Agent;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /** @var string */
    protected $table = 'users';

    /**
     * Get the identifier that will be stored in the JWT subject claim.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'email' => $this->email,
            'role' => $this->role,
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['is_admin'];

    /**
     * Get the agents that belong to the user.
     */
    public function agents()
    {
        return $this->belongsToMany(Agent::class, 'agent_users')
            ->withTimestamps();
    }

    /**
     * Get the transactions created by the user.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    /**
     * Get the agents created by the user.
     */
    public function createdAgents()
    {
        return $this->hasMany(Agent::class, 'created_by');
    }

    /**
     * Get the EOD settlements performed by the user.
     */
    public function eodSettlements()
    {
        return $this->hasMany(EodSettlement::class, 'settled_by');
    }

    /**
     * Get the transaction logs for the user.
     */
    public function transactionLogs()
    {
        return $this->hasMany(TransactionLog::class, 'user_id');
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include admins.
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope a query to only include agents.
     */
    public function scopeAgentRole($query)
    {
        return $query->where('role', 'agent');
    }

    /**
     * Check if user is an admin.
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is an admin (method form for policies and controllers).
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Check if user is an agent.
     */
    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    /**
     * Check if user has access to a specific agent.
     *
     * @param \App\Models\Agent|int $agent Agent model or agent id
     */
    public function hasAgentAccess(Agent|int $agent): bool
    {
        $agentId = $agent instanceof Agent ? $agent->id : $agent;

        // Admins have access to all agents
        if ($this->is_admin) {
            return true;
        }

        // Check if user is associated with the agent
        return $this->agents()->where('agents.id', $agentId)->exists();
    }

    /**
     * Get all accessible agents for the user (default "Khách hàng" + assigned agents).
     */
    public function getAccessibleAgents()
    {
        $default = Agent::getDefaultAgent();
        $defaultCollection = $default ? collect([$default]) : collect();

        if ($this->is_admin) {
            return $defaultCollection->merge(Agent::active()->where('agent_id', '!=', 'Khách hàng')->orderBy('name')->get())->values();
        }

        return $defaultCollection->merge($this->agents()->active()->orderBy('name')->get())->values();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure email is lowercase
        static::creating(function ($user) {
            $user->email = strtolower($user->email);
        });

        static::updating(function ($user) {
            if ($user->isDirty('email')) {
                $user->email = strtolower($user->email);
            }
        });
    }
}
