<?php

namespace App\Support\Merlin;

use App\Models\ProblemCase;
use App\Models\User;
use App\Repositories\Admin\MaterialRequirementNettingRepository;
use App\Repositories\Admin\ProblemCaseRepository;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Admin\MaterialRequirementDemandEligibilityService;
use App\Services\Admin\MaterialRequirementNettingService;
use App\Services\Admin\MaterialShortageProblemCaseResolver;
use Closure;

/** Internal read composition after the case lock; not authorization or runnable admission. */
final readonly class MerlinAdmissionContext
{
    private string $caseId;

    /** @param Closure(): void $assertActive */
    public function __construct(
        private string $name,
        private ProblemCase $lockedCase,
        private Closure $assertActive,
    ) {
        $this->caseId = $lockedCase->id;
    }

    public function connectionName(): string
    {
        ($this->assertActive)();

        return $this->name;
    }

    public function case(): ProblemCase
    {
        ($this->assertActive)();

        return clone $this->lockedCase;
    }

    public function resolveCurrent(): MaterialShortageCurrentEvaluation
    {
        ($this->assertActive)();
        $eligibility = app(MaterialRequirementDemandEligibilityService::class);
        $resolver = new MaterialShortageProblemCaseResolver(
            new ProblemCaseRepository($this->name), $eligibility,
            new MaterialRequirementNettingService(new MaterialRequirementNettingRepository($this->name), $eligibility),
        );
        $result = $resolver->resolve($this->caseId);
        ($this->assertActive)();

        return $result;
    }

    public function freshActor(int $userId): ?User
    {
        ($this->assertActive)();
        $actor = app(UserRepositoryInterface::class)->findForMerlinAdmission($userId, $this->name);
        ($this->assertActive)();

        return $actor;
    }
}
