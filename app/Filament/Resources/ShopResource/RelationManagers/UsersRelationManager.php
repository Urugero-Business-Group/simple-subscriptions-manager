<?php

namespace App\Filament\Resources\ShopResource\RelationManagers;

use App\Models\ShopUser;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasRelationshipTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $recordTitleAttribute = 'shop_id';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('General')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->required(),
                    ]),
                Section::make('Update Password')
                    ->columns(2)
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('password')
                                    ->label('Password')
                                    ->rules(['confirmed', Password::defaults()])
                                    ->autocomplete('password'),
                                TextInput::make('password_confirmation')
                                    ->label('Confirm Password')
                                    ->password()
                                    ->rules([
                                        'required_with:password',
                                    ])
                                    ->autocomplete('password'),
                                Select::make('roles')
                                    ->label('Roles')
                                    ->options(Role::all()->pluck('name', 'id'))
                                    ->multiple()
                                    ->columnSpan(2)
                                    ->searchable()
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->label('Name'),
                TextColumn::make('user.email')
                    ->searchable()
                    ->label('Email'),
                TagsColumn::make('user.roles')
                    ->getStateUsing(function ($record) {
                        $roles = "";

                        foreach ($record->user->roles as $key => $role) {
                            $roles .= $role->name;

                            if ($key < count($record->user->roles) - 1) {
                                $roles .= ", ";
                            }
                        }

                        return $roles;
                    })
                    ->separator(',')
                    ->searchable()
                    ->label('Roles'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (HasRelationshipTable $livewire, array $data): Model {
                        $data['password'] = Hash::make($data['password']);

                        $user = $livewire->getRelationship()->getModel()->user()->create($data);
                        
                        foreach ($data['roles'] as $role) {
                            $user->assignRole($role);
                        }

                        return ShopUser::create([
                            'shop_id' => $livewire->ownerRecord->id,
                            'user_id' => $user->id,
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
