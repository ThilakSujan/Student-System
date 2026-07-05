const express = require('express');
const router = express.Router();
const feeController = require('../controllers/feeController');
const { protect } = require('../middlewares/auth');
const { requireRole } = require('../middlewares/roleMiddleware');

router.use(protect);

router.get('/categories', requireRole('admin'), feeController.getCategories);
router.post('/categories', requireRole('admin'), feeController.createCategory);

router.get('/structures', requireRole('admin', 'staff'), feeController.getStructures);
router.post('/structures', requireRole('admin'), feeController.createStructure);

router.get('/payments', feeController.getPayments);
router.post('/payments', requireRole('admin', 'staff'), feeController.createPayment);

module.exports = router;
