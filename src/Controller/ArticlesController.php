<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Articles;
use App\Entity\Commentaires;
use App\Form\CommentaireFormType;
use App\Form\AjoutArticleFormType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;



    /**
     * Class ArticlesController
     * @package App\Controller
     * @Route("/actualites", name="actualites_")
     */

class ArticlesController extends AbstractController
{
    /**
     * @Route("/", name="articles")
     */
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        //On appelle la liste de tous les articles 
        //$articles = $this->getDoctrine()->getRepository(Articles::class)->findAll();
        //Methode findBy qui permet de récuperer les données avec des critères de filtre et de tri 
        $donnees = $this->getDoctrine()->getRepository(Articles::class)->findBy([],['created_at'=>'desc']);
        $articles = $paginator->paginate(
            $donnees, //On passe les donnees
            $request->query->getInt('page',1), //Numéro de la page en cours, 1 par defaut
            3, //Nombre d'élements qu'on souhaite affiché par page
        );
        //dd($articles); 
        return $this->render('articles/index.html.twig', [
            'articles' => $articles,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("/article/nouveau", name="ajout_article")
    */
    public function ajoutArticle(Request $request){
        //On instancie l'entité Articles
        $article = new Articles();

        //Création de l'objet formulaire 
        $form = $this->createForm(AjoutArticleFormType::class,$article);

        // Nous récupérons les données
        $form->handleRequest($request);
        // Nous vérifions si le formulaire a été soumis et si les données sont valides
        if ($form->isSubmitted() && $form->isValid()) {
            //$this->getUsers = L'id de la personne connectée
            $article->setUsers($this->getUser());

            $doctrine = $this->getDoctrine()->getManager();

            // On hydrate notre instance $article
            $doctrine->persist($article);

            // On écrit en base de données
            $doctrine->flush();

            $this->addFlash("message", 'Votre article a bien été publié');
            // On redirige l'utilisateur
            return $this->redirectToRoute('actualites_articles');
        }

        return $this->render('articles/ajout.html.twig', [
            'articleForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/article/{slug}", name="article")
    */
    public function article($slug, Request $request){
        // On récupère l'article correspondant au slug
        $article = $this->getDoctrine()->getRepository(Articles::class)->findOneBy(['slug' => $slug]);
        //---------------------------------------
        // On récupère les commentaires actifs de l'article
        $commentaires = $this->getDoctrine()->getRepository(Commentaires::class)->findBy([
            'articles' => $article,
            'actif' => 1
        ],['created_at' => 'desc']);
        //--------------------------------------
        if(!$article){
            // Si aucun article n'est trouvé, nous créons une exception
            throw $this->createNotFoundException('L\'article n\'existe pas');
        }
        //On instancie l'entité Commentaires
        $commentaire = new Commentaires();

        //Création de l'objet formulaire 
        $form = $this->createForm(CommentaireFormType::class,$commentaire);

        // Nous récupérons les données
        $form->handleRequest($request);
        // Nous vérifions si le formulaire a été soumis et si les données sont valides
        if ($form->isSubmitted() && $form->isValid()) {
            // Hydrate notre commentaire avec l'article
            $commentaire->setArticles($article);
            $commentaire->setActif("1");
            // Hydrate notre commentaire avec la date et l'heure courants
           //$commentaire->setCreatedAt(new \DateTime('now'));

            $doctrine = $this->getDoctrine()->getManager();

            // On hydrate notre instance $commentaire
            $doctrine->persist($commentaire);

            // On écrit en base de données
            $doctrine->flush();

            // On redirige l'utilisateur
            return $this->redirectToRoute('actualites_article', ['slug' => $slug]);
        }
        // Si l'article existe nous envoyons les données à la vue
        return $this->render('articles/article.html.twig', [
            'article' => $article,
            'commentForm' => $form->createView(),
            'commentaires' => $commentaires
        ]);
    }
}
