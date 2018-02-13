<?php



namespace Boxalino\Intelligence\Controller\Index;

  class Index extends \Magento\Framework\App\Action\Action{

    protected $context;
    protected $data;
    protected $layoutFactory;
    protected $priceLayout;

    public function __construct(
      \Magento\Framework\App\Action\Context $context,
      \Magento\Framework\View\LayoutFactory $layoutFactory,
      \Magento\Framework\Pricing\Render\Layout $priceLayout,
      array $data = []
    )
    {
      header("Access-Control-Allow-Origin: *");
      header('Access-Control-Allow-Credentials: true');
      header('Access-Control-Max-Age: 86400');
      if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
          header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
          header('Access-Control-Allow-Headers: X-PINGOTHER, Content-Type');
      }
      $this->context = $context;
      $this->data = $data;
      $this->layoutFactory = $layoutFactory;
      $this->priceLayout = $priceLayout;

      parent::__construct($context);

    }

      public function execute(){
        $block = $this->layoutFactory->create()->createBlock('Boxalino\Intelligence\Block\Product\ProductList\Parametrized', 'rec', ['data' => []]);

        $pricingBlock = $block->getLayout()->createBlock('Magento\Framework\Pricing\Render', 'product.price.render.default');

        $block->setChild('product.price.render.default', $pricingBlock);

        $pricingRenderBlock = $block->getLayout()->createBlock('Magento\Framework\Pricing\Render\RendererPool', 'render.product.prices');

        $pricingBlock->setPriceRenderHandle('catalog_product_prices');

        $pricingBlock->setLayout($block->getLayout());

        $block->setChild('render.product.prices', $pricingRenderBlock);
        
        if($block->getFormat() == 'json') {
         echo $block->setTemplate("Boxalino_Intelligence::product/recommendation_json.phtml")->toHtml();
        } else {
         echo $block->setTemplate("Boxalino_Intelligence::product/recommendation.phtml")->toHtml();
       }

       }
  }
