<?php

namespace agungsugiarto\boilerplate\Controllers\Users;

use agungsugiarto\boilerplate\Controllers\BaseController;
use agungsugiarto\boilerplate\Entities\MenuEntity;
use agungsugiarto\boilerplate\Models\GroupMenuModel;
use agungsugiarto\boilerplate\Models\MenuModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

/**
 * Class MenuController.
 */
class MenuController extends BaseController
{
    use ResponseTrait;

    protected MenuModel $menu;
    protected GroupMenuModel $groupsMenu;

    public function __construct()
    {
        $this->menu       = new MenuModel();
        $this->groupsMenu = new GroupMenuModel();
    }

    /**
     * Return an array of resource objects, themselves in array format.
     *
     * @return ResponseInterface|string
     */
    public function index()
    {
        if ($this->request->isAJAX()) {
            return $this->respond(['data' => nestable()]);
        }

        return view('agungsugiarto\boilerplate\Views\Menu\index', [
            'title'    => lang('boilerplate.menu.title'),
            'subtitle' => lang('boilerplate.menu.subtitle'),
            'roles'    => $this->authorize->groups(),
            'menus'    => $this->menu->orderBy('sequence', 'asc')->findAll(),
        ]);
    }

    /**
     * Update to sort menu.
     */
    public function new(): ResponseInterface
    {
        $data = $this->request->getJSON();
        $menu = new MenuEntity();

        $this->db->transBegin();

        try {
            $i = 1;

            foreach ($data as $item) {
                if (isset($item->parent_id)) {
                    $menu->parent_id = $item->parent_id;
                    $menu->sequence  = $i++;
                } else {
                    $menu->parent_id = 0;
                    $menu->sequence  = $i++;
                }

                $this->menu->update($item->id, $menu);
            }

            $this->db->transCommit();
        } catch (Exception $e) {
            $this->db->transRollback();

            return $this->fail(lang('boilerplate.menu.msg.msg_fail_order'));
        }

        return $this->respondUpdated([], lang('boilerplate.menu.msg.msg_update'));
    }

    /**
     * Create a new resource object, from "posted" parameters.
     */
    public function create(): RedirectResponse
    {
        $validationRules = [
            'parent_id'   => 'required|numeric',
            'active'      => 'required|numeric',
            'icon'        => 'required|min_length[5]|max_length[55]',
            'route'       => 'required|max_length[255]',
            'title'       => 'required|min_length[2]|max_length[255]',
            'groups_menu' => 'required',
        ];

        if (! $this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
        }

        $this->db->transBegin();

        try {
            $menu            = new MenuEntity();
            $menu->parent_id = $this->request->getPost('parent_id');
            $menu->active    = $this->request->getPost('active');
            $menu->title     = $this->request->getPost('title');
            $menu->icon      = $this->request->getPost('icon');
            $menu->route     = $this->request->getPost('route');
            $menu->sequence  = $menu->sequence() + 1;

            $id = $this->menu->insert($menu);

            foreach ($this->request->getPost('groups_menu') as $groups) {
                $this->groupsMenu->insert([
                    'group_id' => $groups,
                    'menu_id'  => $id,
                ]);
            }

            $this->db->transCommit();
        } catch (Exception $e) {
            $this->db->transRollback();

            return redirect()->back()->with('sweet-error', $e->getMessage());
        }

        return redirect()->back()->with('sweet-success', lang('boilerplate.menu.msg.msg_insert'));
    }

    /**
     * Add or update a model resource, from "posted" properties.
     */
    public function update(int $id): ResponseInterface
    {
        $validationRules = [
            'parent_id'   => 'required|numeric',
            'active'      => 'required|numeric',
            'icon'        => 'required|min_length[5]|max_length[55]',
            'route'       => 'required|max_length[255]',
            'title'       => 'required|min_length[2]|max_length[255]',
            'groups_menu' => 'required',
        ];

        if (! $this->validate($validationRules)) {
            return $this->fail($this->validator->getErrors());
        }

        $data = $this->request->getRawInput();

        $this->db->transBegin();

        try {
            $this->menu->update($id, [
                'parent_id' => $data['parent_id'],
                'active'    => $data['active'],
                'title'     => $data['title'],
                'icon'      => $data['icon'],
                'route'     => $data['route'],
            ]);

            // first remove all groups_menu by id
            $this->db->table('groups_menu')->where('menu_id', $id)->delete();

            foreach ($data['groups_menu'] as $groups) {
                // insert with new
                $this->groupsMenu->insert([
                    'group_id' => $groups,
                    'menu_id'  => $id,
                ]);
            }

            $this->db->transCommit();
        } catch (Exception $e) {
            $this->db->transRollback();

            return $this->fail($e->getMessage());
        }

        return $this->respondUpdated(['id' => $id], lang('boilerplate.menu.msg.msg_update'));
    }

    /**
     * Return the editable properties of a resource object.
     *
     * @return ResponseInterface|void
     */
    public function edit(int $id)
    {
        $found = $this->menu->getMenuById($id);

        if ($this->request->isAJAX()) {
            if (! $found) {
                return $this->failNotFound(lang('boilerplate.menu.msg.msg_get_fail'));
            }

            return $this->respond([
                'data'  => $found,
                'menu'  => $this->menu->getMenu(),
                'roles' => $this->menu->getRole(),
            ]);
        }
    }

    /**
     * Delete the designated resource object from the model.
     */
    public function delete(?int $id = null): ResponseInterface
    {
        if (! $this->menu->delete($id)) {
            return $this->failNotFound(lang('boilerplate.menu.msg.msg_get_fail'));
        }

        return $this->respondDeleted(['id' => $id], lang('boilerplate.menu.msg.msg_delete'));
    }
}
