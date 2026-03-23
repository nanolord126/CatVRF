// backend/src/compliance/compliance.service.ts
import { Injectable, Logger, BadRequestException } from '@nestjs/common';
import { createCipheriv, createDecipheriv, randomBytes, scrypt } from 'crypto';
import { promisify } from 'util';
import { PrismaService } from '../prisma/prisma.service'; // Предполагаем наличие PrismaService
import { ComplianceType, IntegrationStatus } from '@prisma/client';
import axios from 'axios';

@Injectable()
export class ComplianceService {
  private readonly logger = new Logger(ComplianceService.name);
  private readonly algorithm = 'aes-256-gcm';
  private readonly secretKey = process.env.COMPLIANCE_ENCRYPTION_KEY; // Мин. 32 символа

  constructor(private prisma: PrismaService) {}

  /**
   * Шифрование API-токена перед сохранением.
   */
  async encryptToken(token: string): Promise<string> {
    const iv = randomBytes(16);
    const cipher = createCipheriv(this.algorithm, Buffer.from(this.secretKey), iv);
    
    const encrypted = Buffer.concat([cipher.update(token, 'utf8'), cipher.final()]);
    const tag = cipher.getAuthTag();

    return Buffer.concat([iv, tag, encrypted]).toString('base64');
  }

  /**
   * Тест подключения к госсистеме (Честный ЗНАК как пример).
   */
  async testConnection(type: ComplianceType, inn: string, token: string, tenantId: string): Promise<any> {
    const correlationId = randomBytes(8).toString('hex');
    this.logger.log(`Testing connection for ${type}, INN: ${inn}, CID: ${correlationId}`);

    try {
      if (type === ComplianceType.HONEST_SIGN) {
        // Прокси на API Честный ЗНАК (https://честныйзнак.рф/dev/api/)
        const response = await axios.get('https://api.crpt.ru/api/v3/true-api/auth/profile', {
          headers: { 
            'Authorization': `Bearer ${token}`,
            'X-INN': inn,
            'X-Correlation-Id': correlationId
          },
          timeout: 5000,
        });
        
        return { success: response.status === 200, message: 'Успешная авторизация в Честный ЗНАК' };
      }

      // Шаблон для Меркурия или МДЛП
      return { success: false, message: `Система ${type} пока в режиме тестирования.` };
      
    } catch (error) {
      this.logger.error(`Connection test failed for ${type}: ${error.message}`, error.stack);
      return { success: false, message: error.response?.data?.error?.message || 'Ошибка подключения к внешнему API' };
    }
  }

  /**
   * Сохранение новой интеграции.
   */
  async connectIntegration(tenantId: string, type: ComplianceType, inn: string, apiToken: string) {
    const encryptedToken = await this.encryptToken(apiToken);
    
    return this.prisma.complianceIntegration.upsert({
      where: { 
        tenantId_type: { tenantId, type } 
      },
      update: {
        inn,
        encryptedApiTokens: encryptedToken,
        status: IntegrationStatus.CONNECTED,
        lastCheckedAt: new Date(),
        errorMessage: null,
      },
      create: {
        tenantId,
        type,
        inn,
        encryptedApiTokens: encryptedToken,
        status: IntegrationStatus.CONNECTED,
        lastCheckedAt: new Date(),
      },
    });
  }
}
