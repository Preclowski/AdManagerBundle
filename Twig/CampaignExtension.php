<?php

namespace Pasinter\AdManagerBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerAwareInterface,
    Symfony\Component\DependencyInjection\ContainerInterface;


class CampaignExtension extends \Twig_Extension implements ContainerAwareInterface
{

    /**
     *
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     *
     * @param ContainerInterface $container 
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        
        return $this;
    }
    
    public function getFunctions()
    {
        return array(
            'admanager_campaign_get' => new \Twig_Function_Method($this, 'get'),
            'admanager_campaign_render' => new \Twig_Function_Method($this, 'render', array('is_safe' => array('html'))),
            
            'admanager_campaign_render_one' => new \Twig_Function_Method($this, 'renderOne', array('is_safe' => array('html'))),
        );
    }

    
    /**
     *
     * @param string $code
     * @param array $path
     * @param array $options
     * @return type 
     */
    public function get($code, array $options = array())
    {
        $em = $this->container->get('doctrine')->getEntityManager(); 
        
        $qb = $em->getRepository('PasinterAdManagerBundle:Campaign')->createQueryBuilder('c');
        
        $qb
            ->select('c')
            ->innerJoin('c.ads', 'a')
            ->andWhere('c.code = :code')
            ->setParameter('code', $code)
        ;
        
        try {
            $campaign = $qb->getQuery()->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
        
        return $campaign;
    }


    public function render($code, array $options = array(), $renderer = null)
    {
        $campaign = $this->get($code, $options);

        if ($campaign) {
            $html = '';
            foreach ($campaign->getAds() as $ad) {
                $media = $ad->getImage();
                if (!empty($media)) {
                    
                    $mediaservice = $this->container->get('sonata.media.pool');
                    $provider = $mediaservice->getProvider($media->getProviderName());

                    $format = $provider->getFormatName($media, 'samall');
                    
                    $html .= '<div class="item">';
                    $html .= '<a href="' . $ad->getLink() . '" target="_blank">';
                    $html .= '<img src="'.$provider->generatePublicUrl($media, 'reference').'" alt="'.$ad->getLink().'" title="'.$ad->getTitle().'" >';
                    $html .= '</a>';
                    $html .= '</div>';
                }
            }
            
            return $html;
        }
        
        return '';
    }
    
    public function renderOne($code, array $options = array(), $renderer = null)
    {
        $campaign = $this->get($code, $options);

        if($campaign) {
            $html = '<div class="admanaget_render_one" style="width:728px; height: 90px;overflow: hidden;">';

            $ad = $campaign->getAds()->first();
            $media = $ad->getImage();

            $mediaservice = $this->container->get('sonata.media.pool');
            $provider = $mediaservice->getProvider($media->getProviderName());
            $format = $provider->getFormatName($media, 'big');

            $html .= '<a href="' . $ad->getLink() . '">';
            $html .= '<img src="' . $provider->generatePublicUrl($media, $format) .  '" width="728" height="90">';
            $html .= '</a>';

            $html .= '</div>';
            
            return $html;
        }
        
        return '<!-- admanager_campaign_render_one: 0 results -->';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'admanager_campaign';
    }
}
