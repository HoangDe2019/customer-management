import React, { useEffect, useRef, useState } from 'react';
import { generateMoMoQR } from '../api/momo';
import type { MoMoQRResponse } from '../types';

export function MomoQR() {
    const [customerName, setCustomerName] = useState('');
    const [totalAmount, setTotalAmount] = useState('');
    const [transactionType, setTransactionType] = useState<'Đáo' | 'Rút'>('Đáo');
    const [result, setResult] = useState<MoMoQRResponse | null>(null);
    const [loading, setLoading] = useState(false);
    const [paymentStatus, setPaymentStatus] = useState<'pending' | 'checking' | 'success' | 'failed'>('pending');
    const iframeRef = useRef<HTMLIFrameElement | null>(null);
    const statusCheckInterval = useRef<ReturnType<typeof setInterval> | null>(null);

    // Auto-check payment status every 3 seconds
    useEffect(() => {
        if (result && result.orderId) {
            startStatusCheck();
        }

        return () => {
            if (statusCheckInterval.current) {
                clearInterval(statusCheckInterval.current);
            }
        };
    }, [result]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        const amount = parseFloat(totalAmount);
        if (isNaN(amount) || amount < 1000) {
            setResult({
                paymentId: null,
                orderId: null,
                qrCodeUrl: null,
                payUrl: null,
                deeplink: null,
                status: 'failed',
                resultCode: -1,
                message: 'Số tiền tối thiểu 1.000 VNĐ',
            });
            return;
        }
        setLoading(true);
        setResult(null);
        try {
            const res = await generateMoMoQR({
                customer_name: customerName,
                total_amount: amount,
                transaction_type: transactionType,
            });
            setResult(res);
            setPaymentStatus('pending');
        } catch {
            setResult({
                paymentId: null,
                orderId: null,
                qrCodeUrl: null,
                payUrl: null,
                deeplink: null,
                status: 'failed',
                resultCode: -1,
                message: 'Tạo QR thất bại',
            });
        } finally {
            setLoading(false);
        }
    };


    const startStatusCheck = () => {
        if (statusCheckInterval.current) {
            clearInterval(statusCheckInterval.current);
        }

        // Check payment status every 3 seconds
        statusCheckInterval.current = setInterval(async () => {
            if (!result?.orderId) {
                return;
            }

            try {
                const response = await fetch(`/api/momo/check-status/${result.orderId}`, {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
                    },
                });

                const data: { status: string } = await response.json();

                if (data.status === 'success') {
                    setPaymentStatus('success');
                    if (statusCheckInterval.current) {
                        clearInterval(statusCheckInterval.current);
                    }

                    // Show success notification
                    showNotification('Thanh toán thành công!');
                } else if (data.status === 'failed') {
                    setPaymentStatus('failed');
                    if (statusCheckInterval.current) {
                        clearInterval(statusCheckInterval.current);
                    }

                    // Show error notification
                    showNotification('Thanh toán thất bại!');
                } else {
                    setPaymentStatus('checking');
                }
            } catch (error) {
                console.error('Error checking payment status:', error);
            }
        }, 3000);
    };


    const showNotification = (message: string) => {
        // You can use toast library or custom notification
        alert(message);
    };

    const resetPayment = () => {
        setResult(null);
        setPaymentStatus('pending');
        if (statusCheckInterval.current) {
            clearInterval(statusCheckInterval.current);
        }
    };

    return (
        <div className="momo-qr-page">
            <h1>MoMo QR</h1>
            <form onSubmit={handleSubmit} className="card form-card">
                <label>
                    Tên khách hàng *
                    <input
                        value={customerName}
                        onChange={(e) => setCustomerName(e.target.value)}
                        required
                    />
                </label>
                <label>
                    Số tiền (VNĐ) *
                    <input
                        type="number"
                        min={1000}
                        value={totalAmount}
                        onChange={(e) => setTotalAmount(e.target.value)}
                        required
                    />
                </label>
                <label>
                    Loại giao dịch *
                    <select
                        value={transactionType}
                        onChange={(e) => setTransactionType(e.target.value as 'Đáo' | 'Rút')}
                    >
                        <option value="Đáo">Đáo</option>
                        <option value="Rút">Rút</option>
                    </select>
                </label>
                <button type="submit" className="btn btn-primary" disabled={loading}>
                    {loading ? 'Đang tạo...' : 'Tạo QR thanh toán'}
                </button>
            </form>
            {/* Payment Interface */}
            {result && (
                <div className="card momo-result">
                    {/* Status Badge */}
                    <div>
                        <span className={`status-badge status-${paymentStatus}`}>
                            {paymentStatus === 'pending' && '⏳ Chờ thanh toán'}
                            {paymentStatus === 'checking' && '🔍 Đang kiểm tra...'}
                            {paymentStatus === 'success' && '✅ Thanh toán thành công!'}
                            {paymentStatus === 'failed' && '❌ Thanh toán thất bại'}
                        </span>
                    </div>

                    <h3>Chọn phương thức thanh toán:</h3>

                    <div className="payment-methods">
                        {/* Method 1: QR Code */}
                        {result.qrCodeUrl && (
                            <div className="method-card">
                                <h4>📱 Quét mã QR</h4>
                                <img
                                    src={result.qrCodeUrl}
                                    alt="MoMo QR"
                                    className="qr-image"
                                />
                                <p style={{ fontSize: '14px', color: '#666' }}>
                                    Mở app MoMo → Quét mã
                                </p>
                            </div>
                        )}

                        {/* Method 2: Deeplink (Mobile) */}
                        {result?.deeplink && (
                            <div className="method-card">
                                <h4>📲 Mở App MoMo</h4>
                                <div style={{ padding: '40px 0' }}>
                                    <div style={{ fontSize: '60px' }}>💳</div>
                                </div>
                                <a
                                    href={result?.deeplink ?? ''}
                                    className="btn btn-momo"
                                    style={{ width: '100%', textAlign: 'center' }}
                                >
                                    Mở App MoMo
                                </a>
                                <p style={{ fontSize: '14px', color: '#666', marginTop: '10px' }}>
                                    Chỉ hoạt động trên điện thoại
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Method 3: Iframe (Desktop) */}
                    {result?.payUrl && (
                        <div style={{ marginTop: '30px' }}>
                            <h4>🖥️ Thanh toán trực tiếp (Web)</h4>
                            <p style={{ color: '#666', marginBottom: '16px' }}>
                                Trang thanh toán sẽ tải tự động bên dưới:
                            </p>

                            <div className="payment-iframe-container">
                                <iframe
                                    ref={iframeRef}
                                    src={result?.payUrl ?? ''}
                                    title="MoMo Payment"
                                    width="100%"
                                    height="600"
                                    frameBorder="0"
                                    allow="payment"
                                    style={{
                                        border: '2px solid #A50064',
                                        borderRadius: '12px',
                                        boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                                        background: 'white'
                                    }}
                                />
                            </div>
                        </div>
                    )}

                    {/* Reset Button */}
                    <div style={{ marginTop: '20px', textAlign: 'center' }}>
                        <button
                            className="btn"
                            onClick={resetPayment}
                            style={{ background: '#6c757d', color: 'white' }}
                        >
                            ← Tạo mã mới
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
