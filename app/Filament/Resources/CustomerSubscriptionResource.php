<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerSubscriptionResource\Pages;
use App\Filament\Resources\CustomerSubscriptionResource\RelationManagers;
use App\Models\Customer;
use App\Models\CustomerSubscription;
use App\Models\Subscription;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Select;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class CustomerSubscriptionResource extends Resource
{
    protected static ?string $model = CustomerSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()
                    ->columns(4)
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->options(function () {
                                return Customer::all()->mapWithKeys(function ($customer) {
                                    return [$customer->id => $customer->first_name . ' ' . $customer->last_name . ' - ' . $customer->card_number];
                                });
                            })
                            ->searchable()
                            ->columnSpan(2)
                            ->required(),
                        Forms\Components\Select::make('subscription_id')
                            ->label('Subscription')
                            ->options(Subscription::all()->mapWithKeys(function ($subscription) {
                                return [$subscription->id => $subscription->name . ' - ' . $subscription->price . ' RWF'];
                            }))
                            ->searchable()
                            ->columnSpan(2)
                            ->required(),
                        Forms\Components\DatePicker::make('bought_at')
                            ->label('Date')
                            ->columnSpan(2)
                            ->default(now())
                            ->required(),
                        Forms\Components\DatePicker::make('started_at')
                            ->label('Start Date')
                            ->required(),
                        Forms\Components\DatePicker::make('ended_at')
                            ->label('End Date')
                            ->required(),
                        Forms\Components\Textarea::make('comment')
                            ->columnSpanFull()
                            ->maxLength(65535),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.card_number')
                    ->label('Card Number')
                    ->wrap()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.first_name')
                    ->label('First Name')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.last_name')
                    ->label('Last Name')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subscription.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bought_at')
                    ->date()
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(true),
                Tables\Columns\TextColumn::make('started_at')
                    ->date()
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                Tables\Columns\TextColumn::make('ended_at')
                    ->date()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.phone_number')
                    ->label('Phone Number')
                    ->wrap()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.created_at')
                    ->label('Distributor')
                    ->formatStateUsing(fn ($state) => 'WHITEAGLE COMPANY')
                    ->wrap()
                    ->toggleable()
                    ->toggledHiddenByDefault(true),
                Tables\Columns\TextColumn::make('customer.updated_at')
                    ->label('Wholesaler')
                    ->formatStateUsing(fn ($state) => 'BAKAM')
                    ->wrap()
                    ->toggleable()
                    ->toggledHiddenByDefault(true),
                Tables\Columns\TextColumn::make('comment')
                    ->wrap()
                    ->searchable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                Filter::make('bought_at')
                    ->form([
                        Forms\Components\DatePicker::make('bought_from'),
                        Forms\Components\DatePicker::make('bought_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['bought_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('bought_at', '>=', $date),
                            )
                            ->when(
                                $data['bought_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('bought_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['bought_from'] ?? null) {
                            $indicators['bought_from'] = 'Bought since ' . Carbon::parse($data['bought_from'])->toFormattedDateString();
                        }

                        if ($data['bought_until'] ?? null) {
                            $indicators['bought_until'] = 'Bought until ' . Carbon::parse($data['bought_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
                Filter::make('started_at')
                    ->form([
                        Forms\Components\DatePicker::make('started_from'),
                        Forms\Components\DatePicker::make('started_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['started_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('started_at', '>=', $date),
                            )
                            ->when(
                                $data['started_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('started_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['started_from'] ?? null) {
                            $indicators['started_from'] = 'Started since ' . Carbon::parse($data['started_from'])->toFormattedDateString();
                        }

                        if ($data['started_until'] ?? null) {
                            $indicators['started_until'] = 'Started until ' . Carbon::parse($data['started_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
                Filter::make('ended_at')
                    ->form([
                        Forms\Components\DatePicker::make('ended_from'),
                        Forms\Components\DatePicker::make('ended_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['ended_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('ended_at', '>=', $date),
                            )
                            ->when(
                                $data['ended_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('ended_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['ended_from'] ?? null) {
                            $indicators['ended_from'] = 'Ended since ' . Carbon::parse($data['ended_from'])->toFormattedDateString();
                        }

                        if ($data['ended_until'] ?? null) {
                            $indicators['ended_until'] = 'Ended until ' . Carbon::parse($data['ended_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
                Tables\Filters\TrashedFilter::make(),
                Filter::make('Subscription')
                    ->form([
                        Select::make('subscription_id')
                            ->label("Subscription")
                            ->options(Subscription::all()->pluck('name', 'id'))
                            ->searchable(),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['subscription_id']) {
                            return null;
                        }

                        return 'Subscription: ' . Subscription::find($data['subscription_id'])?->name;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return isset($data['subscription_id']) ? $query->where('subscription_id', $data['subscription_id']) : $query;
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                ExportBulkAction::make(),
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerSubscriptions::route('/'),
            'create' => Pages\CreateCustomerSubscription::route('/create'),
            'view' => Pages\ViewCustomerSubscription::route('/{record}'),
            'edit' => Pages\EditCustomerSubscription::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
