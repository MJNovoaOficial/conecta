<?php

namespace Tests\Unit;

use App\Support\VersionedAsset;
use Tests\TestCase;

class VersionedAssetTest extends TestCase
{
    public function test_los_dos_css_usan_la_huella_real_de_su_contenido_y_son_estables(): void
    {
        foreach (['css/premium.css', 'css/responsive.css'] as $path) {
            $expected = asset($path).'?v='.hash_file('sha256', public_path($path));
            $this->assertSame($expected, VersionedAsset::url($path));
            $this->assertSame($expected, VersionedAsset::url($path));
        }
    }

    public function test_cambia_la_version_con_el_contenido_aunque_se_conserve_la_fecha_del_archivo(): void
    {
        $original = public_path();
        $directory = sys_get_temp_dir().'/conecta-assets-'.bin2hex(random_bytes(8));
        mkdir($directory);
        $file = $directory.'/fixture.css';
        $this->app->usePublicPath($directory);
        try {
            file_put_contents($file, 'body{color:red}');
            touch($file, 1700000000);
            $first = VersionedAsset::url('fixture.css');
            file_put_contents($file, 'body{color:tan}');
            touch($file, 1700000000);
            clearstatcache(true, $file);
            $this->assertSame(1700000000, filemtime($file));
            $this->assertNotSame($first, VersionedAsset::url('fixture.css'));
            $sameContent = VersionedAsset::url('fixture.css');
            touch($file, 1800000000);
            clearstatcache(true, $file);
            $this->assertSame($sameContent, VersionedAsset::url('fixture.css'));
        } finally {
            $this->app->usePublicPath($original);
            @unlink($file);
            rmdir($directory);
        }
    }

    public function test_un_archivo_ausente_o_un_directorio_no_rompen_el_formulario(): void
    {
        $this->assertSame(asset('css/archivo-inexistente.css'), VersionedAsset::url('css/archivo-inexistente.css'));
        $this->assertSame(asset('css'), VersionedAsset::url('css'));
    }
}
