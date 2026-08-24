<?php

    namespace App\Services;

    use App\Contracts\CodeGeneratorInterface;
    use App\Enums\SequenceResetType;
    use App\Models\Sequence;
    use Carbon\CarbonImmutable;
    use Illuminate\Support\Facades\DB;

    class CodeGeneratorService implements CodeGeneratorInterface {
        public function generate(CodeGeneratorData $config): string {
            $now = now()->toImmutable();

            return DB::transaction(function () use ($config, $now) {
                $sequence = Sequence::query()->where('sequence_key', $config->sequence_key)->lockForUpdate()->first();

                if ($sequence === null) {
                    $sequence = Sequence::create([
                        'sequence_key' => $config->sequence_key,
                        'current_value' => 0,
                        'last_generated_at' => $now,
                    ]);

                    $sequence = Sequence::query()->whereKey($sequence->id)->lockForUpdate()->first();
                }

                $this->resetSequenceIfNeeded($sequence, $config->reset);

                $sequence->forceFill([
                    'current_value' => $sequence->current_value + 1,
                    'last_generated_at' => $now,
                ]);

                $sequence->save();

                return $this->formatCode(prefix: $config->prefix, number: $sequence->current_value,
                    padding: $config->padding, separator: $config->separator, reset: $config->reset, now: $now);
            });
        }

        protected function resetSequenceIfNeeded(Sequence $sequence, SequenceResetType $reset): void {

            if ($reset === SequenceResetType::NONE) {
                return;
            }

            if (!$sequence->last_generated_at) {
                return;
            }

            $last = $sequence->last_generated_at;

            $now = now()->toImmutable();

            if ($reset === SequenceResetType::YEAR && $last->year !== $now->year) {
                $sequence->current_value = 0;
            }

            if ($reset === SequenceResetType::MONTH && ($last->year !== $now->year || $last->month !== $now->month)) {
                $sequence->current_value = 0;
            }
        }

        protected function formatCode(string $prefix, int $number, int $padding, string $separator,
            SequenceResetType $reset, CarbonImmutable $now): string {
            $number = str_pad((string)$number, $padding, '0', STR_PAD_LEFT);

            return match ($reset) {
                SequenceResetType::YEAR => "{$prefix}{$separator}{$now->format('y')}{$separator}{$number}",
                SequenceResetType::MONTH => "{$prefix}{$separator}{$now->format('ym')}{$separator}{$number}",
                default => "{$prefix}{$separator}{$number}",
            };
        }
    }
