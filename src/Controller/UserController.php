<?php

namespace App\Controller;

use DateTime;
use App\Entity\Form;
use App\Entity\Answer;
use App\Entity\Folder;
use App\Entity\Question;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    private const TIME_BETWEEN_FORM = 12;
    private const TIME_FOR_FORM = 10;
    private const MINIMUM_SCORE = 8;

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/user/homepage", name="user.homepage")
     */
    public function homepage(): Response
    {
        return $this->render('user/homepage.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    /**
     * @Route("/user/quizz", name="user.quizz")
     */
    public function quizz(): Response
    {
        return $this->render('user/quizz.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    /**
     * @Route("/user/newQuizz", name="user.quizz.new")
     */
    public function newQuizz(): Response
    {
        $user = $this->getUser();
        $usersRoles = $user->getRoles();

        if (in_array('ROLE_FORM', $usersRoles)) {
            return $this->redirectToRoute('user.profil');
        }

        $quizz = new Form();

        $lastQuizz = $this->em->getRepository(Form::class)->findLastForm($user->getId());
        if (null !== $lastQuizz) {
            $insertedDate = $lastQuizz->getInserted();
            $nowTime = new DateTime('now');
            $diff = $nowTime->diff($insertedDate);
            if ($diff->h < self::TIME_BETWEEN_FORM) {
                if (!$lastQuizz->getIsSubmitted() && $diff->i < self::TIME_FOR_FORM) {
                    $quizz = $lastQuizz;
                } else {
                    //TODO: show error message
                    return $this->redirectToRoute('user.profil');
                }
            }
        }

        $quizz->setUser($user);
        $quizz->setScore(0);

        $allQuestions = $this->em->getRepository(Question::class)->findAll();

        $randomsQuestions = [];
        $randIndexs = array_rand($allQuestions, 12);
        foreach ($randIndexs as $index) {
            $randomsQuestions[] = $allQuestions[$index];
        }

        foreach ($randomsQuestions as $question) {
            $quizz->addQuestion($question);
        }

        $this->em->persist($quizz);
        $this->em->flush();

        return $this->render('user/newQuizz.html.twig', [
            'quizz' => $quizz,
            'user' => $this->getUser(),
        ]);
    }

    /**
     * @Route("/user/validateQuizz", name="user.quizz.validate")
     */
    public function validateQuizz(Request $request): Response
    {
        $quizzArray = $request->request->get('quizz');
        $user = $this->getUser();
        $quizz = $this->em->getRepository(Form::class)->findOneById($quizzArray['id']);

        if ($quizz->getUser()->getId() !== $user->getId()
            || $quizz->getIsSubmitted()
        ) {
            //Trying to validate quizz from another account
            return $this->redirectToRoute('user.profil');
        }
        //TODO: check time
        $currentScore = 0;
        foreach($quizzArray['questions'] as $questionId => $questionArray) {
            $question = $this->em->getRepository(Question::class)->findOneById($questionId);
            if (null === $question) {
                return $this->redirectToRoute('user.profil');
            }
            $questionAnswers = $question->getAnswers();
            
            $isQuestionValid = true;
            foreach ($questionArray as $answerId => $answerGiven) {
                $answer = $this->em->getRepository(Answer::class)->findOneById($answerId);

                if (!$questionAnswers->contains($answer)) {
                    return $this->redirectToRoute('user.profil');
                }

                $requestedAnswer = $answer->getIsCorrect();
                if ($requestedAnswer && 'true' !== $answerGiven) {
                    $isQuestionValid = false;
                } else if (!$requestedAnswer && 'false' !== $answerGiven) {
                    $isQuestionValid = false;
                }
            }

            if ($isQuestionValid) {
                $currentScore++;
            }
        }

        $quizz->setScore($currentScore);
        $quizz->setIsSumbitted(true);
        $this->em->persist($quizz);

        if ($currentScore > self::MINIMUM_SCORE) {
            $userRoles = $user->getRoles();
            $userRoles[] = 'ROLE_FORM';
            $user->setRoles($userRoles);
            $this->em->persist($user);
        }

        $this->em->flush();

        //TODO: JSONResponse
        return $this->redirectToRoute('user.homepage');
    }


    /**
     * @Route("/user/rules", name="user.rules")
     */
    public function rules(): Response
    {
        return $this->render('user/rules.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
    /**
     * @Route("/user/profil", name="user.profil")
     */
    public function profil(): Response
    {
        return $this->render('user/profil.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    /**
     * @Route("/user/newFolder", name="user.folder.new")
     */
    public function newFolder(): Response
    {
        $user = $this->getUser();
        $usersRoles = $user->getRoles();

        if (!in_array('ROLE_FORM', $usersRoles) || in_array('ROLE_VALIDATED', $usersRoles)) {
            return $this->redirectToRoute('user.profil');
        }

        $folder = $this->em->getRepository(Folder::class)->findOneByUser($user);
        //State 1 = waiting for verification, state 2 = accepted
        if (null !== $folder && in_array($folder->getState(), [1, 2])) {
            return $this->redirectToRoute('user.profil');
        }

        return $this->render('user/newFolder.html.twig', [
            'user' => $this->getUser()
        ]);
    }

    /**
     * @Route("/user/validateForm", name="user.form.validate")
     */
    public function validateForm(Request $request): Response
    {
        $user = $this->getUser();
        $usersRoles = $user->getRoles();
        if (!in_array('ROLE_FORM', $usersRoles) || in_array('ROLE_VALIDATED', $usersRoles)) {
            return $this->redirectToRoute('user.profil');
        }
        $folderArray = json_decode($request->request->get('folder'), true);

        $userFolders = $user->getFolders();
        if (!empty($userFolders)) {
            foreach ($userFolders as $userFolder) {
                //State 1 = waiting for verification, state 2 = accepted
                if (in_array($userFolders->getState(), [1, 2])) {
                    return $this->redirectToRoute('user.profil');
                }
            }
        }

        $folder = new Folder();
        $folder->setUser($user);
        $folder->setNames($folderArray['names']);
        $folder->setSexe(('men' === $folderArray['sexe']) ? 1 : 0);
        $folder->setAge(intval($folderArray['age']));
        $folder->setBackground($folderArray['background']);
        $folder->setSide($folderArray['side']);
        $folder->setJob($folderArray['job']);
        $folder->setHrpAge(intval($folderArray['hrpAge']));
        $folder->setHrpExperience(intval($folderArray['hrpExperience']));
        $folder->setHrpProvenance($folderArray['hrpProvenance']);
        $folder->setState(1);

        $this->em->persist($folder);
        $this->em->flush();

        //TODO: JSONResponse
        return $this->redirectToRoute('user.homepage');
    }

    /**
     * @Route("/douane/listForm", name="douane.folder.list")
     */
    public function listForm(): Response
    {
        $user = $this->getUser();
        $usersRoles = $user->getRoles();
        if (!in_array('ROLE_DOUANIER', $usersRoles)) {
            return $this->redirectToRoute('user.profil');
        }
        
        return $this->render('douane/listform.html.twig', [
            'user' => $this->getUser()
        ]);
    }

    /**
     * @Route("/douane/form/id/{id}", name="douane.folder.edit")
     * 
     * @param string $id
     * @return Response
     */
    public function editForm(string $id): Response
    {
        $user = $this->getUser();
        $usersRoles = $user->getRoles();
        if (!in_array('ROLE_DOUANIER', $usersRoles)) {
            return $this->redirectToRoute('user.profil');
        }
        $folderRepo = $this->getDoctrine()->getRepository(Folder::class);
        
        return $this->render('douane/editFolder.html.twig', [
            'folder' => $folderRepo->findOneById($id),
            'user' => $this->getUser()
        ]);
    }

    /**
     * @Route("/ajax/form", name="ajax.folder.list")
     */
    public function formAjax(Request $request)
    {
        $user = $this->getUser();
        $usersRoles = $user->getRoles();
        if (!in_array('ROLE_DOUANIER', $usersRoles)) {
            return $this->redirectToRoute('user.profil');
        }

        $folderRepo = $this->getDoctrine()->getRepository(Folder::class);
        $params = $request->request->all();
        return new JsonResponse($folderRepo->getListFolders($params));
    }

    /**
     * @Route("/ajax/form/validate", name="ajax.folder.validate")
     */
    public function folderAjaxValidate(Request $request)
    {
        $user = $this->getUser();
        $usersRoles = $user->getRoles();
        if (!in_array('ROLE_DOUANIER', $usersRoles)) {
            return $this->redirectToRoute('user.profil');
        }

        $folderRepo = $this->getDoctrine()->getRepository(Folder::class);
        $params = $request->request->all();
        $currentFolder = $folderRepo->findOneById($params['id']);
        
        //State 1 = waiting for verification, state 2 = accepted
        if (1 !== $currentFolder->getState()) {
            return $this->redirectToRoute('user.profil');
        }

        $currentFolder->setState($params['state']);

        if (0 === $params['state']) {
            $currentUser = $currentFolder->getUser();
            $userRoles = $currentUser->getRoles();
            $userRoles[] = 'ROLE_FOLDER';
            $currentUser->setRoles($userRoles);
            $this->em->persist($currentUser);
        }

        $this->em->persist($currentFolder);
        $this->em->flush();

        return new JsonResponse();
    }
}
