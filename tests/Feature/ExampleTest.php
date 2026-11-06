<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_sin_sesion_manda_al_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_la_pantalla_de_login_carga(): void
    {
        $this->get('/login')->assertOk()->assertSee('Contraseña');
    }
}
