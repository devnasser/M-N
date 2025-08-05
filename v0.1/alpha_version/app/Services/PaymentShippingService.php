<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaymentShippingService
{
    /**
     * معالجة الدفع عبر مدى
     */
    public function processMadaPayment($paymentData): array
    {
        $madaData = [
            'transaction_id' => 'MADA-' . time(),
            'amount' => $paymentData['amount'],
            'currency' => 'SAR',
            'card_number' => $this->maskCardNumber($paymentData['card_number']),
            'card_type' => 'Mada',
            'merchant_id' => config('payment.mada_merchant_id', 'TEST_MERCHANT'),
            'terminal_id' => config('payment.mada_terminal_id', 'TEST_TERMINAL'),
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'authorization_code' => $this->generateAuthorizationCode(),
            'response_code' => '00', // نجح
            'response_message' => 'Transaction Approved'
        ];
        
        return [
            'success' => true,
            'payment_data' => $madaData,
            'receipt_url' => $this->generateReceiptUrl($madaData['transaction_id']),
            'qr_code' => $this->generatePaymentQRCode($madaData),
            'next_steps' => [
                'Send confirmation email',
                'Update order status',
                'Initiate shipping process'
            ]
        ];
    }
    
    /**
     * معالجة الدفع عبر STC Pay
     */
    public function processSTCPayPayment($paymentData): array
    {
        $stcPayData = [
            'transaction_id' => 'STCPAY-' . time(),
            'amount' => $paymentData['amount'],
            'currency' => 'SAR',
            'phone_number' => $paymentData['phone_number'],
            'merchant_id' => config('payment.stc_merchant_id', 'TEST_STC_MERCHANT'),
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'authorization_code' => $this->generateAuthorizationCode(),
            'response_code' => '00',
            'response_message' => 'Payment Successful'
        ];
        
        return [
            'success' => true,
            'payment_data' => $stcPayData,
            'receipt_url' => $this->generateReceiptUrl($stcPayData['transaction_id']),
            'sms_confirmation' => $this->sendSMSConfirmation($paymentData['phone_number'], $stcPayData),
            'next_steps' => [
                'Send confirmation SMS',
                'Update order status',
                'Initiate shipping process'
            ]
        ];
    }
    
    /**
     * معالجة الدفع عبر تمارا (التقسيط)
     */
    public function processTamaraPayment($paymentData): array
    {
        $tamaraData = [
            'transaction_id' => 'TAMARA-' . time(),
            'amount' => $paymentData['amount'],
            'currency' => 'SAR',
            'installments' => $paymentData['installments'] ?? 3,
            'monthly_payment' => $paymentData['amount'] / ($paymentData['installments'] ?? 3),
            'customer_info' => [
                'name' => $paymentData['customer_name'],
                'phone' => $paymentData['phone_number'],
                'email' => $paymentData['email']
            ],
            'merchant_id' => config('payment.tamara_merchant_id', 'TEST_TAMARA_MERCHANT'),
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'approval_status' => 'approved',
            'payment_schedule' => $this->generatePaymentSchedule($paymentData['amount'], $paymentData['installments'] ?? 3)
        ];
        
        return [
            'success' => true,
            'payment_data' => $tamaraData,
            'contract_url' => $this->generateContractUrl($tamaraData['transaction_id']),
            'payment_reminders' => $this->setupPaymentReminders($tamaraData),
            'next_steps' => [
                'Send contract to customer',
                'Setup payment reminders',
                'Update order status'
            ]
        ];
    }
    
    /**
     * معالجة الدفع عبر تابي (التقسيط)
     */
    public function processTabbyPayment($paymentData): array
    {
        $tabbyData = [
            'transaction_id' => 'TABBY-' . time(),
            'amount' => $paymentData['amount'],
            'currency' => 'SAR',
            'installments' => $paymentData['installments'] ?? 4,
            'monthly_payment' => $paymentData['amount'] / ($paymentData['installments'] ?? 4),
            'customer_info' => [
                'name' => $paymentData['customer_name'],
                'phone' => $paymentData['phone_number'],
                'email' => $paymentData['email']
            ],
            'merchant_id' => config('payment.tabby_merchant_id', 'TEST_TABBY_MERCHANT'),
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'approval_status' => 'approved',
            'payment_schedule' => $this->generatePaymentSchedule($paymentData['amount'], $paymentData['installments'] ?? 4)
        ];
        
        return [
            'success' => true,
            'payment_data' => $tabbyData,
            'contract_url' => $this->generateContractUrl($tabbyData['transaction_id']),
            'payment_reminders' => $this->setupPaymentReminders($tabbyData),
            'next_steps' => [
                'Send contract to customer',
                'Setup payment reminders',
                'Update order status'
            ]
        ];
    }
    
    /**
     * حساب الشحن عبر SMSA
     */
    public function calculateSMSAShipping($shippingData): array
    {
        $baseCost = 25.00;
        $weightCost = $shippingData['weight'] * 2.5;
        $distanceCost = $shippingData['distance'] * 0.5;
        $totalCost = $baseCost + $weightCost + $distanceCost;
        
        return [
            'carrier' => 'SMSA Express',
            'service_type' => $shippingData['service_type'] ?? 'Standard',
            'cost' => round($totalCost, 2),
            'delivery_time' => $this->calculateDeliveryTime($shippingData['service_type'] ?? 'Standard'),
            'tracking_available' => true,
            'pickup_available' => true,
            'insurance_included' => true,
            'features' => [
                'Real-time tracking',
                'SMS notifications',
                'Multiple pickup locations',
                'Insurance coverage'
            ]
        ];
    }
    
    /**
     * حساب الشحن عبر Aramex
     */
    public function calculateAramexShipping($shippingData): array
    {
        $baseCost = 30.00;
        $weightCost = $shippingData['weight'] * 3.0;
        $distanceCost = $shippingData['distance'] * 0.6;
        $totalCost = $baseCost + $weightCost + $distanceCost;
        
        return [
            'carrier' => 'Aramex',
            'service_type' => $shippingData['service_type'] ?? 'Standard',
            'cost' => round($totalCost, 2),
            'delivery_time' => $this->calculateDeliveryTime($shippingData['service_type'] ?? 'Standard'),
            'tracking_available' => true,
            'pickup_available' => true,
            'insurance_included' => true,
            'features' => [
                'Real-time tracking',
                'Email notifications',
                'Multiple pickup locations',
                'Insurance coverage',
                'International shipping'
            ]
        ];
    }
    
    /**
     * حساب الشحن عبر البريد السعودي
     */
    public function calculateSaudiPostShipping($shippingData): array
    {
        $baseCost = 15.00;
        $weightCost = $shippingData['weight'] * 1.5;
        $distanceCost = $shippingData['distance'] * 0.3;
        $totalCost = $baseCost + $weightCost + $distanceCost;
        
        return [
            'carrier' => 'Saudi Post',
            'service_type' => $shippingData['service_type'] ?? 'Standard',
            'cost' => round($totalCost, 2),
            'delivery_time' => $this->calculateDeliveryTime($shippingData['service_type'] ?? 'Standard'),
            'tracking_available' => true,
            'pickup_available' => false,
            'insurance_included' => false,
            'features' => [
                'Basic tracking',
                'Post office pickup',
                'Affordable rates',
                'Wide coverage'
            ]
        ];
    }
    
    /**
     * إنشاء شحنة جديدة
     */
    public function createShipment($shipmentData): array
    {
        $trackingNumber = 'TRK-' . strtoupper(uniqid());
        
        $shipment = [
            'tracking_number' => $trackingNumber,
            'carrier' => $shipmentData['carrier'],
            'service_type' => $shipmentData['service_type'],
            'origin' => $shipmentData['origin'],
            'destination' => $shipmentData['destination'],
            'weight' => $shipmentData['weight'],
            'dimensions' => $shipmentData['dimensions'],
            'items' => $shipmentData['items'],
            'created_at' => now()->format('Y-m-d H:i:s'),
            'status' => 'Created',
            'estimated_delivery' => $this->calculateDeliveryTime($shipmentData['service_type']),
            'shipping_label' => $this->generateShippingLabel($trackingNumber),
            'pickup_scheduled' => $this->schedulePickup($trackingNumber)
        ];
        
        return [
            'success' => true,
            'shipment' => $shipment,
            'tracking_url' => $this->generateTrackingUrl($trackingNumber, $shipmentData['carrier']),
            'next_steps' => [
                'Print shipping label',
                'Schedule pickup',
                'Send tracking info to customer'
            ]
        ];
    }
    
    /**
     * تتبع الشحنة
     */
    public function trackShipment($trackingNumber, $carrier): array
    {
        $status = $this->getRandomStatus();
        $location = $this->getRandomLocation();
        
        $trackingInfo = [
            'tracking_number' => $trackingNumber,
            'carrier' => $carrier,
            'current_status' => $status,
            'current_location' => $location,
            'last_update' => now()->format('Y-m-d H:i:s'),
            'estimated_delivery' => now()->addDays(2)->format('Y-m-d'),
            'tracking_history' => $this->generateTrackingHistory($trackingNumber),
            'delivery_options' => $this->getDeliveryOptions($status),
            'carrier_contact' => $this->getCarrierContact($carrier)
        ];
        
        return [
            'success' => true,
            'tracking_info' => $trackingInfo
        ];
    }
    
    /**
     * إلغاء الشحنة
     */
    public function cancelShipment($trackingNumber, $reason): array
    {
        $refundAmount = $this->calculateRefundAmount($trackingNumber);
        
        $cancellationData = [
            'tracking_number' => $trackingNumber,
            'cancellation_reason' => $reason,
            'cancelled_at' => now()->format('Y-m-d H:i:s'),
            'refund_amount' => $refundAmount,
            'refund_status' => 'Processing',
            'refund_tracking' => $this->generateRefundTracking([
                'tracking_number' => $trackingNumber,
                'refund_amount' => $refundAmount,
                'reason' => $reason
            ])
        ];
        
        return [
            'success' => true,
            'cancellation_data' => $cancellationData,
            'next_steps' => [
                'Process refund',
                'Update order status',
                'Notify customer'
            ]
        ];
    }
    
    // Private helper methods
    private function maskCardNumber($cardNumber): string
    {
        return '****-****-****-' . substr($cardNumber, -4);
    }
    
    private function generateAuthorizationCode(): string
    {
        return strtoupper(uniqid());
    }
    
    private function generateReceiptUrl($transactionId): string
    {
        return url("/receipt/{$transactionId}");
    }
    
    private function generatePaymentQRCode($paymentData): string
    {
        return "data:image/png;base64," . base64_encode("QR_CODE_FOR_" . $paymentData['transaction_id']);
    }
    
    private function sendSMSConfirmation($phoneNumber, $paymentData): array
    {
        return [
            'sent' => true,
            'phone_number' => $phoneNumber,
            'message' => "Payment successful. Transaction ID: {$paymentData['transaction_id']}",
            'sent_at' => now()->format('Y-m-d H:i:s')
        ];
    }
    
    private function generatePaymentSchedule($amount, $installments): array
    {
        $monthlyPayment = $amount / $installments;
        $schedule = [];
        
        for ($i = 1; $i <= $installments; $i++) {
            $schedule[] = [
                'installment_number' => $i,
                'amount' => round($monthlyPayment, 2),
                'due_date' => now()->addMonths($i)->format('Y-m-d'),
                'status' => 'Pending'
            ];
        }
        
        return $schedule;
    }
    
    private function generateContractUrl($transactionId): string
    {
        return url("/contract/{$transactionId}");
    }
    
    private function setupPaymentReminders($paymentData): array
    {
        return [
            'email_reminders' => true,
            'sms_reminders' => true,
            'reminder_schedule' => [
                '3_days_before' => true,
                '1_day_before' => true,
                'on_due_date' => true
            ]
        ];
    }
    
    private function calculateDeliveryTime($serviceType): string
    {
        switch ($serviceType) {
            case 'Express':
                return '1-2 business days';
            case 'Standard':
                return '3-5 business days';
            case 'Economy':
                return '5-7 business days';
            default:
                return '3-5 business days';
        }
    }
    
    private function generateTrackingUrl($trackingNumber, $carrier): string
    {
        return url("/tracking/{$trackingNumber}");
    }
    
    private function getPickupLocations($city): array
    {
        return [
            'locations' => [
                [
                    'name' => 'Main Branch',
                    'address' => 'Main Street, ' . $city,
                    'phone' => '+966-11-123-4567',
                    'hours' => '8:00 AM - 6:00 PM'
                ],
                [
                    'name' => 'Downtown Branch',
                    'address' => 'Downtown Area, ' . $city,
                    'phone' => '+966-11-123-4568',
                    'hours' => '9:00 AM - 7:00 PM'
                ]
            ]
        ];
    }
    
    private function getPostOffices($city): array
    {
        return [
            'post_offices' => [
                [
                    'name' => 'Central Post Office',
                    'address' => 'Central Area, ' . $city,
                    'phone' => '+966-11-123-4569',
                    'hours' => '8:00 AM - 4:00 PM'
                ],
                [
                    'name' => 'North Post Office',
                    'address' => 'North Area, ' . $city,
                    'phone' => '+966-11-123-4570',
                    'hours' => '8:00 AM - 4:00 PM'
                ]
            ]
        ];
    }
    
    private function generateShippingLabel($trackingNumber): string
    {
        return "SHIPPING_LABEL_FOR_{$trackingNumber}";
    }
    
    private function schedulePickup($trackingNumber): array
    {
        return [
            'scheduled' => true,
            'pickup_date' => now()->addDay()->format('Y-m-d'),
            'pickup_time' => '10:00 AM - 2:00 PM',
            'tracking_number' => $trackingNumber
        ];
    }
    
    private function getRandomStatus(): string
    {
        $statuses = ['In Transit', 'Out for Delivery', 'Delivered', 'Pending Pickup'];
        return $statuses[array_rand($statuses)];
    }
    
    private function getRandomLocation(): string
    {
        $locations = [
            'Riyadh Distribution Center',
            'Jeddah Distribution Center',
            'Dammam Distribution Center',
            'Local Post Office'
        ];
        return $locations[array_rand($locations)];
    }
    
    private function generateTrackingHistory($trackingNumber): array
    {
        return [
            [
                'status' => 'Shipment Created',
                'location' => 'Origin Facility',
                'timestamp' => now()->subDays(3)->format('Y-m-d H:i:s'),
                'description' => 'Shipment has been created and is being processed'
            ],
            [
                'status' => 'In Transit',
                'location' => 'Distribution Center',
                'timestamp' => now()->subDays(2)->format('Y-m-d H:i:s'),
                'description' => 'Shipment is in transit to destination'
            ],
            [
                'status' => 'Out for Delivery',
                'location' => 'Local Facility',
                'timestamp' => now()->subDay()->format('Y-m-d H:i:s'),
                'description' => 'Shipment is out for delivery'
            ]
        ];
    }
    
    private function getCarrierContact($carrier): array
    {
        return [
            'phone' => '+966-11-123-4567',
            'email' => 'support@' . strtolower(str_replace(' ', '', $carrier)) . '.com',
            'website' => 'https://www.' . strtolower(str_replace(' ', '', $carrier)) . '.com'
        ];
    }
    
    private function getDeliveryOptions($status): array
    {
        if ($status === 'Out for Delivery') {
            return [
                'Home delivery',
                'Pickup from facility',
                'Reschedule delivery'
            ];
        }
        
        return ['Standard delivery'];
    }
    
    private function calculateRefundAmount($trackingNumber): float
    {
        return 25.00; // Mock refund amount
    }
    
    private function generateRefundTracking($cancellationData): array
    {
        return [
            'refund_id' => 'REF-' . time(),
            'tracking_number' => $cancellationData['tracking_number'],
            'refund_amount' => $cancellationData['refund_amount'],
            'refund_reason' => $cancellationData['reason'],
            'refund_status' => 'Processing',
            'estimated_processing_time' => '3-5 business days'
        ];
    }
} 