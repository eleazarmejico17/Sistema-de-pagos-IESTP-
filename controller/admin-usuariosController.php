<?php
require_once __DIR__ . '/../models/admin-usuariosModel.php';

class EstudiantesController {

    public function listar() {
        $est = new Estudiantes();
        return $est->getAll();
    }

    public function crear($post) {
        $est = new Estudiantes();

        // Imagen (opcional)
        $foto = null;
        if (!empty($_FILES['foto_est']['name'])) {
            $nombre = time() . "_" . $_FILES['foto_est']['name'];
            move_uploaded_file(
                $_FILES['foto_est']['tmp_name'],
                __DIR__ . '/../uploads/' . $nombre
            );
            $foto = $nombre;
        }

        $post['foto_est'] = $foto;

        return $est->create($post);
    }

    public function obtener($id) {
        $est = new Estudiantes();
        return $est->getById($id);
    }

    public function actualizar($id, $post) {
        $est = new Estudiantes();
        return $est->update($id, $post);
    }

    public function eliminar($id) {
        $est = new Estudiantes();
        return $est->delete($id);
    }
}
