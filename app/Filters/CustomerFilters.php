<?php

    namespace App\Filters\Customer;

    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Http\Request;

    class CustomerFilters {
        public function __construct(protected Request $request) {
        }

        public function apply(Builder $query): Builder {
            return $query->when($this->request->filled('search'), fn(Builder $query) => $this->search($query))
                ->when($this->request->filled('status'), fn(Builder $query) => $this->status($query))
                ->when($this->request->filled('type'), fn(Builder $query) => $this->type($query));
        }

        protected function search(Builder $query): Builder {
            $search = trim($this->request->string('search'));

            return $query->where(function (Builder $query) use ($search) {

                $query->where('customer_name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('telephone_number', 'like', "%{$search}%");

            });
        }

        protected function status(Builder $query): Builder {
            return $query->where('status', $this->request->string('status'));
        }

        protected function type(Builder $query): Builder {
            return $query->where('type', $this->request->string('type'));
        }
    }
