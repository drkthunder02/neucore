<?php
namespace Brave\Core\Api\User;

use Brave\Core\Entity\Group;
use Brave\Core\Entity\GroupRepository;
use Brave\Core\Entity\Player;
use Brave\Core\Entity\PlayerRepository;
use Brave\Core\Service\UserAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * @SWG\Tag(
 *     name="Group",
 *     description="Group management."
 * )
 */
class GroupController
{
    private $res;

    private $log;

    private $gr;

    private $pr;

    private $uas;

    private $em;

    private $namePattern = "/^[-._a-zA-Z0-9]+$/";

    /**
     * @var Group
     */
    private $group;

    /**
     * @var Player
     */
    private $player;

    public function __construct(Response $res, LoggerInterface $log, GroupRepository $gr,
        PlayerRepository $pr, UserAuthService $uas, EntityManagerInterface $em)
    {
        $this->log = $log;
        $this->res = $res;
        $this->gr = $gr;
        $this->pr = $pr;
        $this->uas = $uas;
        $this->em = $em;
    }

    /**
     * @SWG\Get(
     *     path="/user/group/all",
     *     operationId="all",
     *     summary="List all groups.",
     *     description="Needs role: user, group-admin or app-admin",
     *     tags={"Group"},
     *     security={{"Session"={}}},
     *     @SWG\Response(
     *         response="200",
     *         description="List of groups.",
     *         @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/Group"))
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function all()
    {
        return $this->res->withJson($this->gr->findAll());
    }

    /**
     * @SWG\Post(
     *     path="/user/group/create",
     *     operationId="create",
     *     summary="Create a group.",
     *     description="Needs role: group-admin",
     *     tags={"Group"},
     *     security={{"Session"={}}},
     *     consumes={"application/x-www-form-urlencoded"},
     *     @SWG\Parameter(
     *         name="name",
     *         in="formData",
     *         required=true,
     *         description="Name of the group.",
     *         type="string",
     *         maxLength=64,
     *         pattern="^[-._a-zA-Z0-9]+$"
     *     ),
     *     @SWG\Response(
     *         response="201",
     *         description="The new group.",
     *         @SWG\Schema(ref="#/definitions/Group")
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Group name is invalid."
     *     ),
     *     @SWG\Response(
     *         response="409",
     *         description="A group with this name already exists."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function create(Request $request)
    {
        $name = $request->getParam('name', '');
        if (! preg_match($this->namePattern, $name)) {
            return $this->res->withStatus(400);
        }

        if ($this->otherGroupExists($name)) {
            return $this->res->withStatus(409);
        }

        $group = new Group();
        $group->setName($name);

        try {
            $this->em->persist($group);
            $this->em->flush();
        } catch (\Exception $e) {
            $this->log->critical($e->getMessage(), ['exception' => $e]);
            return $this->res->withStatus(500);
        }

        return $this->res->withJson($group);
    }

    /**
     * @SWG\Put(
     *     path="/user/group/{id}/rename",
     *     operationId="rename",
     *     summary="Rename a group.",
     *     description="Needs role: group-admin",
     *     tags={"Group"},
     *     security={{"Session"={}}},
     *     consumes={"application/x-www-form-urlencoded"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the group.",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         in="formData",
     *         required=true,
     *         description="New name for the group.",
     *         type="string",
     *         maxLength=64,
     *         pattern="^[-._a-zA-Z0-9]+$"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Group was renamed.",
     *         @SWG\Schema(ref="#/definitions/Group")
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Group not found."
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Group name is invalid."
     *     ),
     *     @SWG\Response(
     *         response="409",
     *         description="A group with this name already exists."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function rename($id, Request $request)
    {
        if (! $this->findGroup($id)) {
            return $this->res->withStatus(404);
        }

        $name = $request->getParam('name', '');
        if (! preg_match($this->namePattern, $name)) {
            return $this->res->withStatus(400);
        }

        if ($this->otherGroupExists($name, $this->group->getId())) {
            return $this->res->withStatus(409);
        }

        $this->group->setName($name);
        try {
            $this->em->flush();
        } catch (\Exception $e) {
            $this->log->critical($e->getMessage(), ['exception' => $e]);
            return $this->res->withStatus(500);
        }

        return $this->res->withJson($this->group);
    }

    /**
     * @SWG\Delete(
     *     path="/user/group/{id}/delete",
     *     operationId="delete",
     *     summary="Delete a group.",
     *     description="Needs role: group-admin",
     *     tags={"Group"},
     *     security={{"Session"={}}},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the group.",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="204",
     *         description="Group was deleted."
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Group not found."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function delete($id)
    {
        if (! $this->findGroup($id)) {
            return $this->res->withStatus(404);
        }

        try {
            $this->em->remove($this->group);
            $this->em->flush();
        } catch (\Exception $e) {
            $this->log->critical($e->getMessage(), ['exception' => $e]);
            return $this->res->withStatus(500);
        }

        return $this->res->withStatus(204);
    }

    /**
     * @SWG\Get(
     *     path="/user/group/{id}/managers",
     *     operationId="managers",
     *     summary="List all managers of a group.",
     *     description="Needs role: group-admin",
     *     tags={"Group"},
     *     security={{"Session"={}}},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Group ID.",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="List of players ordered by name. Only id and name properties are returned.",
     *         @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/Player"))
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Group not found."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function managers($id)
    {
        return $this->getPlayersFromGroup($id, 'managers', false);
    }

    /**
     * @SWG\Put(
     *     path="/user/group/{id}/add-manager/{player}",
     *     operationId="addManager",
     *     summary="Assign a player as manager to a group.",
     *     description="Needs role: group-admin",
     *     tags={"Group"},
     *     security={{"Session"={}}},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the group.",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="player",
     *         in="path",
     *         required=true,
     *         description="ID of the player.",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="204",
     *         description="Player added as manager."
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Player and/or group not found."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function addManager($id, $player)
    {
        return $this->addPlayerAs($id, $player, 'manager', false);
    }

    /**
     * @SWG\Put(
     *     path="/user/group/{id}/remove-manager/{player}",
     *     operationId="removeManager",
     *     summary="Remove a manager (player) from a group.",
     *     description="Needs role: group-admin",
     *     tags={"Group"},
     *     security={{"Session"={}}},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the group.",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="player",
     *         in="path",
     *         required=true,
     *         description="ID of the player.",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="204",
     *         description="Player removed from managers."
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Player and/or group not found."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function removeManager($id, $player)
    {
        return $this->removePlayerFrom($id, $player, 'manager', false);
    }

    /**
     * @SWG\Get(
     *     path="/user/group/{id}/applicants",
     *     operationId="applicants",
     *     summary="List all applicants of a group.",
     *     description="Needs role: group-manager",
     *     tags={"Group"},
     *     security={{"Session"={}}},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Group ID.",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="List of players ordered by name. Only id and name properties are returned.",
     *         @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/Player"))
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Group not found."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function applicants($id)
    {
        return $this->getPlayersFromGroup($id, 'applicants', true);
    }

    /**
     * @SWG\Put(
     *     path="/user/group/{id}/add-member/{player}",
     *     operationId="addMember",
     *     summary="Adds a player to a group.",
     *     description="Needs role: group-manager",
     *     tags={"Group"},
     *     security={{"Session"={}}},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the group.",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="player",
     *         in="path",
     *         required=true,
     *         description="ID of the player.",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="204",
     *         description="Player added."
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Player and/or group not found."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function addMember($id, $player)
    {
        return $this->addPlayerAs($id, $player, 'player', true);
    }

    /**
     * @SWG\Put(
     *     path="/user/group/{id}/remove-member/{player}",
     *     operationId="removeMember",
     *     summary="Remove player from a group.",
     *     description="Needs role: group-manager",
     *     tags={"Group"},
     *     security={{"Session"={}}},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the group.",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="player",
     *         in="path",
     *         required=true,
     *         description="ID of the player.",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="204",
     *         description="Player removed."
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Player and/or group not found."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function removeMember($id, $player)
    {
        return $this->removePlayerFrom($id, $player, 'player', true);
    }

    /**
     * Retruns true if another group with that name already exists.
     *
     * @param string $name Group name.
     * @param int $id Group ID.
     * @return boolean
     */
    private function otherGroupExists(string $name, int $id = null): bool
    {
        $group = $this->gr->findOneBy(['name' => $name]);

        if ($group === null) {
            return false;
        }

        if ($group->getId() === $id) {
            return false;
        }

        return true;
    }

    private function getPlayersFromGroup($groupId, $type, $onlyIfManager)
    {
        if (! $this->findGroup($groupId)) {
            return $this->res->withStatus(404);
        }

        if ($onlyIfManager && ! $this->checkManager($this->group)) {
            return $this->res->withStatus(403);
        }

        $players = [];
        if ($type === 'managers') {
            $players = $this->group->getManagers();
        } elseif ($type === 'applicants') {
            $players = $this->group->getApplicants();
        }

        $ret = [];
        foreach ($players as $player) {
            $ret[] = [
                'id' => $player->getId(),
                'name' => $player->getName()
            ];
        }

        return $this->res->withJson($ret);
    }

    private function addPlayerAs($groupId, $playerId, $type, $onlyIfManager)
    {
        if (! $this->findGroupAndPlayer($groupId, $playerId)) {
            return $this->res->withStatus(404);
        }

        if ($onlyIfManager && ! $this->checkManager($this->group)) {
            return $this->res->withStatus(403);
        }

        if ($type === 'manager') {
            $hasGroups = $this->player->getManagerGroups();
        } elseif ($type === 'player') {
            $hasGroups = $this->player->getGroups();
        }

        $add = true;
        foreach ($hasGroups as $gp) {
            if ($gp->getId() === $this->group->getId()) {
                $add = false;
                break;
            }
        }

        if ($add && $type === 'manager') {
            $this->group->addManager($this->player);
        } elseif ($add && $type === 'player') {
            $this->player->addGroup($this->group);
        }

        try {
            $this->em->flush();
        } catch (\Exception $e) {
            $this->log->critical($e->getMessage(), ['exception' => $e]);
            return $this->res->withStatus(500);
        }

        return $this->res->withStatus(204);
    }

    public function removePlayerFrom($id, $player, $type, $onlyIfManager)
    {
        if (! $this->findGroupAndPlayer($id, $player)) {
            return $this->res->withStatus(404);
        }

        if ($onlyIfManager && ! $this->checkManager($this->group)) {
            return $this->res->withStatus(403);
        }

        if ($type === 'manager') {
            $this->group->removeManager($this->player);
        } elseif ($type === 'player') {
            $this->player->removeGroup($this->group);
        }

        try {
            $this->em->flush();
        } catch (\Exception $e) {
            $this->log->critical($e->getMessage(), ['exception' => $e]);
            return $this->res->withStatus(500);
        }

        return $this->res->withStatus(204);
    }

    private function findGroup($id)
    {
        $this->group = $this->gr->find($id);
        if ($this->group === null) {
            return false;
        }

        return true;
    }

    private function findGroupAndPlayer($id, $player)
    {
        $this->group = $this->gr->find((int) $id);
        $this->player = $this->pr->find((int) $player);

        if ($this->group === null || $this->player === null) {
            return false;
        }

        return true;
    }

    /**
     * Checks if current logged in user is manager of a group.
     *
     * @param Group $group
     * @return boolean
     */
    private function checkManager(Group $group): bool
    {
        $currentPlayer = $this->uas->getUser()->getPlayer();
        foreach ($currentPlayer->getManagerGroups() as $mg) {
            if ($mg->getId() === $group->getId()) {
                return true;
            }
        }

        return false;
    }
}
